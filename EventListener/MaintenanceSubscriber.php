<?php

namespace Eghojansu\Bundle\SetupBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Eghojansu\Bundle\SetupBundle\Service\Setup;
use Eghojansu\Bundle\SetupBundle\EghojansuSetupBundle;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MaintenanceSubscriber implements EventSubscriberInterface
{
    /** @var Eghojansu\Bundle\SetupBundle\Service\Setup */
    private $setup;

    public function __construct(Setup $setup)
    {
        $this->setup = $setup;
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
            if (!$this->isRequestToBundle($request) &&
                !$this->isMaintenanceRequest($request)) {
                $path = $request->getBaseUrl() . $this->setup->getConfig('maintenance_path');
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
     * Create access denied response
     * @return Symfony\Component\HttpFoundation\Response
     */
    private function createAccessDeniedResponse()
    {
        return new Response('Access Denied', 403);
    }

    /**
     * Check if current request path is a maintenance path
     * @param  Request $request
     * @return boolean
     */
    private function isMaintenanceRequest(Request $request)
    {
        $maintenancePath = $this->setup->getConfig('maintenance_path');
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
}
