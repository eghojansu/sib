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
        $request = $event->getRequest();
        if ($this->setup->isMaintenance() &&
            $this->notInMaintenanceRequest($request)) {
            $path = $request->getBaseUrl() . $this->setup->getConfig('maintenance_path');
            $response = new RedirectResponse($path);

            $event->setResponse($response);
        }
    }

    private function notInMaintenanceRequest(Request $request)
    {
        $currentRoute = $request->get('_route');
        $currentPath = $request->getPathInfo();
        $maintenancePath = $this->setup->getConfig('maintenance_path');
        $prefix = EghojansuSetupBundle::BUNDLE_ID;

        $pattern1 = "#^$maintenancePath#i";
        $pattern2 = "#^$prefix#i";

        return !(preg_match($pattern1, $currentPath) ||
            preg_match($pattern2, $currentRoute));
    }
}
