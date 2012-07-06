<?php

namespace AntiMattr\GoogleBundle\Helper;

use AntiMattr\GoogleBundle\Analytics;
use AntiMattr\GoogleBundle\Analytics\Event;
use Symfony\Component\Templating\Helper\Helper;

class AnalyticsHelper extends Helper
{
    private $analytics;
    private $sourceHttps;
    private $sourceHttp;
    private $sourceEndpoint;

    public function __construct(Analytics $analytics, $sourceHttps, $sourceHttp, $sourceEndpoint)
    {
        $this->analytics = $analytics;
        $this->sourceHttps = $sourceHttps;
        $this->sourceHttp = $sourceHttp;
        $this->sourceEndpoint = $sourceEndpoint;
    }

    public function getAllowAnchor($trackerKey)
    {
        return $this->analytics->getAllowAnchor($trackerKey);
    }

    public function getAllowHash($trackerKey)
    {
        return $this->analytics->getAllowHash($trackerKey);
    }

    public function getAllowLinker($trackerKey)
    {
        return $this->analytics->getAllowLinker($trackerKey);
    }

    public function getCurrentPageTracking($trackerKey)
    {
        return $this->analytics->getCurrentPageTracking($trackerKey);
    }

    public function getTrackerName($trackerKey)
    {
        if ($this->analytics->getIncludeNamePrefix($trackerKey)) {
            return $this->analytics->getTrackerName($trackerKey).'.';
        }
        return "";
    }

    public function getSiteSpeedSampleRate($trackerKey)
    {
        return $this->analytics->getSiteSpeedSampleRate($trackerKey);
    }

    public function hasCustomPageView($trackerKey = null)
    {
        return $this->analytics->hasCustomPageView($trackerKey);
    }

    public function getCustomPageView($trackerKey = null)
    {
        return $this->analytics->getCustomPageView($trackerKey);
    }

    public function hasCustomVariables()
    {
        return $this->analytics->hasCustomVariables();
    }

    public function getCustomVariables()
    {
        return $this->analytics->getCustomVariables();
    }

    public function hasEventQueue($trackerKey = null)
    {
        return $this->analytics->hasEventQueue($trackerKey);
    }

    public function getEventQueue($trackerKey = null)
    {
        return $this->analytics->getEventQueue($trackerKey);
    }

    public function hasItems($trackerKey = null)
    {
        return $this->analytics->hasItems($trackerKey);
    }

    public function getItems($trackerKey = null)
    {
        return $this->analytics->getItems($trackerKey);
    }

    public function getRequestUri()
    {
        return $this->analytics->getRequestUri();
    }

    public function hasPageViewQueue($trackerKey = null)
    {
        return $this->analytics->hasPageViewQueue($trackerKey);
    }

    public function getPageViewQueue($trackerKey = null)
    {
        return $this->analytics->getPageViewQueue($trackerKey);
    }

    public function getSourceHttps()
    {
        return $this->sourceHttps;
    }

    public function getSourceHttp()
    {
        return $this->sourceHttp;
    }

    public function getSourceEndpoint()
    {
        return $this->sourceEndpoint;
    }

    public function getTrackers(array $trackers = array())
    {
        return $this->analytics->getTrackers($trackers);
    }
    
    public function getApiKey()
    {
        return $this->analytics->getApiKey();
    }
    
    public function getClientId()
    {
        return $this->analytics->getClientId();
    }
    
    public function getTableId()
    {
        return $this->analytics->getTableId();
    }

    public function isTransactionValid($trackerKey = null)
    {
        return $this->analytics->isTransactionValid($trackerKey);
    }

    public function getTransaction($trackerKey = null)
    {
        return $this->analytics->getTransaction($trackerKey);
    }

    public function getName()
    {
        return 'google_analytics';
    }
}
