<?php

namespace AntiMattr\GoogleBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use UnitedPrototype\GoogleAnalytics\Session;
use UnitedPrototype\GoogleAnalytics\Visitor;
use AntiMattr\GoogleBundle\Analytics;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

class ServerSideTrackersListener implements EventSubscriberInterface
{
    private $analytics;

    public function __construct(Analytics $analytics)
    {
        $this->analytics = $analytics;
    }

    public function onKernelTerminate(PostResponseEvent $event)
    {
        $request = $event->getRequest();

        $trackingSession = $this->getTrackingSession($request);
        $visitor = $this->getVisitor($request);

        $visitor->addSession($trackingSession);

        $this->analytics->serverSpread($request, $visitor, $trackingSession);
    }

    private function getVisitor(Request $request)
    {
        $visitor = new Visitor();

        if ($request->getSession()->has('google_analytics/visitor')) {
            $visitor = $request->getSession()->get('google_analytics/visitor');
        } elseif ($request->cookies->has('__utma')) {
            $visitor->fromUtma($request->cookies->get('__utma'));
        } else {
            $visitor->fromServerVar($_SERVER);
        }

        $request->getSession()->set('google_analytics/visitor', $visitor);

        return $visitor;
    }

    private function getTrackingSession(Request $request)
    {
        $session = new Session();

        if ($request->getSession()->has('google_analytics/tracking_session')) {
            $session = $request->getSession()->get('google_analytics/tracking_session');
        } elseif ($request->cookies->has('__utmb')) {
            $session->fromUtmb($request->cookies->get('__utmb'));
        }

        $request->getSession()->set('google_analytics/tracking_session', $session);

        return $session;
    }

    static public function getSubscribedEvents()
    {
        return array(KernelEvents::TERMINATE => 'onKernelTerminate');
    }
}