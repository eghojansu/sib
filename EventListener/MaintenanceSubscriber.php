<?php

namespace Eghojansu\Bundle\SetupBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
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

    public function onKernelRequest(GetResponseEvent $event)
    {
        if ($event->isMasterRequest() && $this->setup->isMaintenance()) {
            $request = $event->getRequest();
            if ($this->isBlacklist($request)) {
                $path = $request->getBaseUrl() . $this->setup->getConfig('maintenance_path');
                $response = new RedirectResponse($path);

                $event->setResponse($response);
            }
        }
    }

    private function isBlacklist(Request $request)
    {
        $currentRoute = $request->get('_route');
        $prefix = EghojansuSetupBundle::BUNDLE_ID;
        if (preg_match("#^$prefix#", $currentRoute)) {
            return false;
        }

        $maintenancePath = $this->setup->getConfig('maintenance_path');
        $currentPath = $request->getPathInfo();
        if (preg_match("#^$maintenancePath$#", $currentPath)) {
            return false;
        }

        return true;
    }
}
