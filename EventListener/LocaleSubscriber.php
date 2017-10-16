<?php

namespace Eghojansu\Bundle\SetupBundle\EventListener;

use Symfony\Component\HttpKernel\KernelEvents;
use Eghojansu\Bundle\SetupBundle\Service\Setup;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LocaleSubscriber implements EventSubscriberInterface
{
    /** @var Eghojansu\Bundle\SetupBundle\Service\Setup */
    private $setup;

    /** @var string */
    private $defaultLocale;

    public function __construct(Setup $setup, $defaultLocale = 'en')
    {
        $this->defaultLocale = $defaultLocale;
        $this->setup = $setup;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if ($this->setup->getConfig('disable') ||
            $this->setup->getConfig('disable_locale') ||
            !$request->hasPreviousSession()) {
            return;
        }

        // try to see if the locale has been set as a _locale routing parameter
        if ($locale = $request->attributes->get('_locale')) {
            $request->getSession()->set('_locale', $locale);
        } else {
            // if no explicit locale has been set on this request, use one from the session
            $request->setLocale(
                $request->getSession()->get('_locale', $this->defaultLocale)
            );
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            // must be registered after the default Locale listener
            KernelEvents::REQUEST => array(array('onKernelRequest', 15)),
        );
    }
}
