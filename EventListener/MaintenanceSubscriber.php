<?php

namespace Eghojansu\Bundle\SetupBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Eghojansu\Bundle\SetupBundle\Service\Setup;
use Eghojansu\Bundle\SetupBundle\EghojansuSetupBundle;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MaintenanceSubscriber implements EventSubscriberInterface
{
    /** @var Eghojansu\Bundle\SetupBundle\Service\Setup */
    private $setup;

    /** @var Psr\Log\LoggerInterface */
    private $logger;

    /** @var Symfony\Component\DependencyInjection\ContainerInterface */
    private $container;

    public function __construct(Setup $setup, ContainerInterface $container, LoggerInterface $logger)
    {
        $this->setup = $setup;
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
    */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    /**
     * Listen to maintenance status changes
     * @param  GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->isMasterRequest()) {
            if ($this->isBundleNotDisabled($event)) {
                $this->guardMaintenance($event);
            }
        }
    }

    /**
     * Guard maintenance
     * @param  GetResponseEvent $event
     */
    private function guardMaintenance(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($this->setup->isMaintenance()) {
            if ($this->preventRequestAccessOnMaintenance($request)) {
                $maintenance = $this->setup->getConfig('maintenance');
                $path = $request->getBaseUrl() . $maintenance['path'];
                $response = new RedirectResponse($path);

                $event->setResponse($response);
            }
        } else {
            if (!$this->isRequestToBundle($request) &&
                $this->isMaintenanceRequest($request)) {
                $event->setResponse($this->createAccessDeniedResponse());
            }
        }
    }

    /**
     * Perform check to request to bundle controller
     * if bundle disabled all access to bundle route will forbidden
     * @param  GetResponseEvent $event
     * @return boolean
     */
    private function isBundleNotDisabled(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($this->setup->getConfig('disable') &&
            ($this->isRequestToBundle($request) ||
                $this->isMaintenanceRequest($request)
            )) {
            $event->setResponse($this->createAccessDeniedResponse());

            return false;
        }

        return true;
    }

    /**
     * Prevent Request Access On Maintenance
     * @param  Request $request
     * @return boolean
     */
    private function preventRequestAccessOnMaintenance(Request $request)
    {
        if ($this->isRequestToBundle($request)) {
            return false;
        } elseif ($this->isMaintenanceRequest($request)) {
            return false;
        } elseif ($this->isWhitelistRequest($request)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Create access denied response
     * @return Symfony\Component\HttpFoundation\Response
     */
    private function createAccessDeniedResponse()
    {
        $content = 'Access Denied';
        if ($this->container->has('twig')) {
            $view = '@EghojansuSetup/Error/403.html.twig';
            $content = $this->container->get('twig')->render($view);
        }

        return new Response($content, 403);
    }

    /**
     * Check if current request path is a maintenance path
     * @param  Request $request
     * @return boolean
     */
    private function isMaintenanceRequest(Request $request)
    {
        $maintenance = $this->setup->getConfig('maintenance');
        $maintenancePath = $maintenance['path'];
        $currentPath = $request->getPathInfo();

        return (bool) preg_match("#^$maintenancePath$#", $currentPath);
    }

    /**
     * Check if current request route is a member of bundle route
     * @param  Request $request
     * @return boolean
     */
    private function isRequestToBundle(Request $request)
    {
        $currentRoute = $request->get('_route');
        $prefix = EghojansuSetupBundle::BUNDLE_ID;

        return (bool) preg_match("#^$prefix#", $currentRoute);
    }

    /**
     * Check if current request path is a in white list
     * @param  Request $request
     * @return boolean
     */
    private function isWhitelistRequest(Request $request)
    {
        $maintenance = $this->setup->getConfig('maintenance');
        $whitelistPath = $maintenance['whitelist_path'];
        $currentPath = $request->getPathInfo();

        return $whitelistPath && preg_match(
            '#^('.implode('|', $whitelistPath).')#',
            $currentPath
        );
    }
}
