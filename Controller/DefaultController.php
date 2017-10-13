<?php

namespace Eghojansu\Bundle\SetupBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Eghojansu\Bundle\SetupBundle\Service\Setup;
use Eghojansu\Bundle\SetupBundle\Event\SetupEvent;
use Eghojansu\Bundle\SetupBundle\Service\FormBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="eghojansu_setup_homepage")
     * @Method({"GET","POST"})
     */
    public function indexAction(Request $request, FormBuilder $formBuilder, Setup $setup)
    {
    	if ($setup->isAuthenticated()) {
    		return $this->redirectToRoute('eghojansu_setup_versions');
    	}

		$form = $formBuilder->createLoginForm();
    	$form->handleRequest($request);
    	if ($form->isSubmitted() && $form->isValid()) {
    		$setup->setAuthenticated(true);

			return $this->redirectToRoute('eghojansu_setup_maintenance');
    	}

        return $this->render('EghojansuSetupBundle:Default:index.html.twig', [
        	'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/maintenance", name="eghojansu_setup_maintenance")
     * @Method({"GET","POST"})
     */
    public function maintenanceAction(Request $request, FormBuilder $formBuilder, Setup $setup)
    {
        $this->notSecure($setup);

        $form = $formBuilder->createMaintenanceForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $setup->setMaintenance($form['maintenance']->getData(), $request);
            $this->addFlash('note', 'Status maintenance sudah diperbaharui');

            return $this->redirectToRoute('eghojansu_setup_versions');
        }

        return $this->render('EghojansuSetupBundle:Default:maintenance.html.twig', [
            'maintenance' => $setup->isMaintenance(),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/versions", name="eghojansu_setup_versions")
     * @Method({"GET"})
     */
    public function versionsAction(Setup $setup)
    {
        if ($this->notSecure($setup)) {
            return $this->redirectToRoute('eghojansu_setup_maintenance');
        }

        return $this->render('EghojansuSetupBundle:Default:versions.html.twig', [
            'versions' => $setup->getVersions(),
        ]);
    }

    /**
     * @Route("/versions/{version}/performed", name="eghojansu_setup_performed")
     * @Method({"GET"})
     */
    public function performedAction(Setup $setup, $version)
    {
        if ($this->notSecure($setup)) {
            return $this->redirectToRoute('eghojansu_setup_maintenance');
        }

        return $this->render('EghojansuSetupBundle:Default:performed.html.twig', [
        	'version' => $version,
        ]);
    }

    /**
     * @Route("/versions/{version}", name="eghojansu_setup_config")
     * @Method({"GET", "POST"})
     */
    public function configAction(Request $request, FormBuilder $formBuilder, Setup $setup, EventDispatcherInterface $eventDispatcher, SetupEvent $event, $version)
    {
        if ($this->notSecure($setup)) {
            return $this->redirectToRoute('eghojansu_setup_maintenance');
        }

        $error = null;
        if (!$setup->isVersionExists($version)) {
            $error = sprintf('Version "%s" was not exists', $version);
        } elseif ($setup->isVersionInstalled($version)) {
            $error = sprintf('Version "%s" has been installed', $version);
        } elseif (!$setup->isPreviousVersionInstalled($version)) {
            $error = sprintf('Previous version prior to "%s" has not been installed', $version);
        }

        if ($error) {
            $this->addFlash('error', $error);

            return $this->redirectToRoute('eghojansu_setup_versions');
        }

        $info = $setup->getVersion($version);
        $hasConfig = count($info['config']) > 0 || count($info['parameters']['sources']) > 0;

        $form = $formBuilder->createConfigForm($version);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $setup->updateParameters($version, $data);

            $event->setVersion($version);
            $eventDispatcher->dispatch(SetupEvent::POST_CONFIG, $event);

            $this->addFlash('message', sprintf('Instalasi versi %s berhasil dijalankan.', $version));

            $message = $event->getMessage();
            if ($message) {
                $this->addFlash('message', $message);
            }

            $setup->recordSetupHistory($version, $request);

            return $this->redirectToRoute('eghojansu_setup_performed', [
                'version' => $version,
            ]);
        }

        return $this->render('EghojansuSetupBundle:Default:config.html.twig', [
            'version' => $version,
            'info' => $info,
            'hasConfig' => $hasConfig,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/done", name="eghojansu_setup_done")
     * @Method({"GET"})
     */
    public function doneAction(Setup $setup)
    {
    	$this->notSecure($setup);

    	$setup->setAuthenticated(false);

    	return $this->redirectToRoute('eghojansu_setup_homepage');
    }

    private function notSecure(Setup $setup, $onMaintenanceOnly = true)
    {
    	if (!$setup->isAuthenticated()) {
    		throw $this->createAccessDeniedException();
    	}

        return $onMaintenanceOnly ? !$setup->isMaintenance() : false;
    }
}
