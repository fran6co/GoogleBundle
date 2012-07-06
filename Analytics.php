<?php

namespace AntiMattr\GoogleBundle;

use AntiMattr\GoogleBundle\Analytics\CustomVariable;
use AntiMattr\GoogleBundle\Analytics\Event;
use AntiMattr\GoogleBundle\Analytics\Item;
use AntiMattr\GoogleBundle\Analytics\Transaction;
use Symfony\Component\DependencyInjection\ContainerInterface;

use UnitedPrototype\GoogleAnalytics\Session;
use UnitedPrototype\GoogleAnalytics\Visitor;
use UnitedPrototype\GoogleAnalytics\Tracker;

class Analytics
{
    const EVENT_QUEUE_KEY      = 'google_analytics/event/queue';
    const CUSTOM_PAGE_VIEW_KEY = 'google_analytics/page_view';
    const PAGE_VIEW_QUEUE_KEY  = 'google_analytics/page_view/queue';
    const TRANSACTION_KEY      = 'google_analytics/transaction';
    const ITEMS_KEY            = 'google_analytics/items';

    private $container;
    private $customVariables = array();
    private $pageViewsWithBaseUrl = true;
    private $trackers;
    private $whitelist;
    private $api_key;
    private $client_id;
    private $table_id;
    private $currentSession = array();

    public function __construct(ContainerInterface $container,
            array $trackers = array(), array $whitelist = array(), array $dashboard = array())
    {
        $this->container = $container;
        $this->trackers = $trackers;
        $this->whitelist = $whitelist;
        $this->api_key = isset($dashboard['api_key']) ? $dashboard['api_key'] : '';
        $this->client_id = isset($dashboard['client_id']) ? $dashboard['client_id'] : '';
        $this->table_id = isset($dashboard['table_id']) ? $dashboard['table_id'] : '';
    }

    public function excludeBaseUrl()
    {
        $this->pageViewsWithBaseUrl = false;
    }

    public function includeBaseUrl()
    {
        $this->pageViewsWithBaseUrl = true;
    }

    private function isValidConfigKey($trackerKey)
    {
        if (!array_key_exists($trackerKey, $this->trackers)) {
            throw new \InvalidArgumentException(sprintf('There is no tracker configuration assigned with the key "%s".', $trackerKey));
        }
        return true;
    }

    private function setTrackerProperty($tracker, $property, $value)
    {
        if ($this->isValidConfigKey($tracker)) {
            $this->trackers[$tracker][$property] = $value;
        }
    }

    private function getTrackerProperty($tracker, $property)
    {
        if (!$this->isValidConfigKey($tracker)) {
            return;
        }

        if (array_key_exists($property, $this->trackers[$tracker])) {
            return $this->trackers[$tracker][$property];
        }

        return null;
    }

    /**
     * @param string $trackerKey
     * @param boolean $allowAnchor
     */
    public function setAllowAnchor($trackerKey, $allowAnchor)
    {
        $this->setTrackerProperty($trackerKey, 'allowAnchor', $allowAnchor);
    }

    /**
     * @param string $trackerKey
     * @return boolean $allowAnchor (default:false)
     */
    public function getAllowAnchor($trackerKey)
    {
        if (null === ($property = $this->getTrackerProperty($trackerKey, 'allowAnchor'))) {
            return false;
        }
        return $property;
    }

    public function getCurrentPageTracking($trackerKey)
    {
        if (null === ($property = $this->getTrackerProperty($trackerKey, 'currentPageTracking'))) {
            return true;
        }
        return $property;
    }

    public function setCurrentPageTracking($trackerKey, $value)
    {
        $this->setTrackerProperty($trackerKey, 'currentPageTracking', $value);
    }

    public function getTrackAjax($trackerKey)
    {
        if (null === ($property = $this->getTrackerProperty($trackerKey, 'trackAjax'))) {
            return false;
        }
        return $property;
    }

    public function setTrackAjax($trackerKey, $value)
    {
        $this->setTrackerProperty($trackerKey, 'trackAjax', $value);
    }

    /**
     * @param string $trackerKey
     * @param boolean $allowHash
     */
    public function setAllowHash($trackerKey, $allowHash)
    {
        $this->setTrackerProperty($trackerKey, 'allowHash', $allowHash);
    }

    /**
     * @param string $trackerKey
     * @return boolean $allowHash (default:false)
     */
    public function getAllowHash($trackerKey)
    {
        if (null === ($property = $this->getTrackerProperty($trackerKey, 'allowHash'))) {
            return false;
        }
        return $property;
    }

    /**
     * @param string $trackerKey
     * @param boolean $allowLinker
     */
    public function setAllowLinker($trackerKey, $allowLinker)
    {
        $this->setTrackerProperty($trackerKey, 'allowLinker', $allowLinker);
    }

    /**
     * @param string $trackerKey
     * @return boolean $allowLinker (default:true)
     */
    public function getAllowLinker($trackerKey)
    {
        if (null === ($property = $this->getTrackerProperty($trackerKey, 'allowLinker'))) {
            return true;
        }
        return $property;
    }

    /**
     * @param string $trackerKey
     * @param boolean $includeNamePrefix
     */
    public function setIncludeNamePrefix($trackerKey, $includeNamePrefix)
    {
        $this->setTrackerProperty($trackerKey, 'includeNamePrefix', $includeNamePrefix);
    }

    /**
     * @param string $trackerKey
     * @return boolean $includeNamePrefix (default:true)
     */
    public function getIncludeNamePrefix($trackerKey)
    {
        if (null === ($property = $this->getTrackerProperty($trackerKey, 'includeNamePrefix'))) {
            return true;
        }
        return $property;
    }

    /**
     * @param string $trackerKey
     * @param boolean $name
     */
    public function setTrackerName($trackerKey, $name)
    {
        $this->setTrackerProperty($trackerKey, 'name', $name);
    }

    /**
     * @param string $trackerKey
     * @return string $name
     */
    public function getTrackerName($trackerKey)
    {
        return $this->getTrackerProperty($trackerKey, 'name');
    }

    /**
     * @param string $trackerKey
     * @param int $siteSpeedSampleRate
     */
    public function setSiteSpeedSampleRate($trackerKey, $siteSpeedSampleRate)
    {
        $this->setTrackerProperty($trackerKey, 'setSiteSpeedSampleRate', $siteSpeedSampleRate);
    }

    /**
     * @param string $trackerKey
     * @return int $siteSpeedSampleRate (default:null)
     */
    public function getSiteSpeedSampleRate($trackerKey)
    {
        if (null != ($property = $this->getTrackerProperty($trackerKey, 'setSiteSpeedSampleRate'))) {
            return (int) $property;
        }
    }

    /**
     * @param string $trackerKey
     *
     * @return string $customPageView
     */
    public function getCustomPageView($trackerKey = null)
    {
        return $this->getOnce(self::CUSTOM_PAGE_VIEW_KEY, $trackerKey);
    }

    /**
     * @param string $trackerKey
     *
     * @return boolean $hasCustomPageView
     */
    public function hasCustomPageView($trackerKey = null)
    {
        return $this->has(self::CUSTOM_PAGE_VIEW_KEY, $trackerKey);
    }

    /**
     * @param string $customPageView
     * @param string $trackerKey
     */
    public function setCustomPageView($customPageView, $trackerKey = null)
    {
        $this->set(self::CUSTOM_PAGE_VIEW_KEY, $customPageView, $trackerKey);
    }

    /**
     * @param CustomVariable $customVariable
     */
    public function addCustomVariable(CustomVariable $customVariable)
    {
        $this->customVariables[] = $customVariable;
    }

    /**
     * @return array $customVariables
     */
    public function getCustomVariables()
    {
        return $this->customVariables;
    }

    /**
     * @return boolean $hasCustomVariables
     */
    public function hasCustomVariables()
    {
        if (!empty($this->customVariables)) {
            return true;
        }
        return false;
    }

    /**
     * @param Event $event
     * @param string $trackerKey
     */
    public function enqueueEvent(Event $event, $trackerKey = null)
    {
        $this->add(self::EVENT_QUEUE_KEY, $event, $trackerKey);
    }

    /**
     * @param array $eventQueue
     * @param string $trackerKey
     */
    public function getEventQueue($trackerKey = null)
    {
        return $this->getOnce(self::EVENT_QUEUE_KEY, $trackerKey);
    }

    /**
     * @param string $trackerKey
     *
     * @return boolean $hasEventQueue
     */
    public function hasEventQueue($trackerKey = null)
    {
        return $this->has(self::EVENT_QUEUE_KEY, $trackerKey);
    }

    /**
     * @param Item $item
     * @param string $trackerKey
     */
    public function addItem(Item $item, $trackerKey = null)
    {
        $this->add(self::ITEMS_KEY, $item, $trackerKey);
    }

    /**
     * @param string $trackerKey
     *
     * @return boolean $hasItems
     */
    public function hasItems($trackerKey = null)
    {
        return $this->has(self::ITEMS_KEY, $trackerKey);
    }

    /**
     * @param Item $item
     * @param string $trackerKey
     *
     * @return boolean $hasItem
     */
    public function hasItem(Item $item, $trackerKey = null)
    {
        if (!$this->hasItems($trackerKey)) {
            return false;
        }
        $items = $this->getItemsFromSession($trackerKey);

        return in_array($item, $items, true);
    }

    /**
     * @param array $items
     * @param string $trackerKey
     */
    public function setItems($items, $trackerKey = null)
    {
        $this->set(self::ITEMS_KEY, $items, $trackerKey);
    }

    /**
     * @param string $trackerKey
     *
     * @return array
     */
    public function getItems($trackerKey = null)
    {
        return $this->getOnce(self::ITEMS_KEY, $trackerKey);
    }

    /**
     * @param string $pageView
     * @param string $trackerKey
     */
    public function enqueuePageView($pageView, $trackerKey = null)
    {
        $this->add(self::PAGE_VIEW_QUEUE_KEY, $pageView, $trackerKey);
    }

    /**
     * @param array $pageViewQueue
     * @param string $trackerKey
     */
    public function getPageViewQueue($trackerKey = null)
    {
        return $this->getOnce(self::PAGE_VIEW_QUEUE_KEY, $trackerKey);
    }

    /**
     * @param string $trackerKey
     *
     * @return boolean $hasPageViewQueue
     */
    public function hasPageViewQueue($trackerKey = null)
    {
        return $this->has(self::PAGE_VIEW_QUEUE_KEY, $trackerKey);
    }

    /**
     * @return Symfony\Component\HttpFoundation\Request $request
     */
    public function getRequest()
    {
        return $this->container->get('request');
    }

    /**
     * Check and apply base url configuration
     * If a GET param whitelist is declared,
     * Then only allow the whitelist
     *
     * @return string $requestUri
     */
    public function getRequestUri($request = null)
    {
        if (null === $request) {
            $request = $this->getRequest();
        }
        $path = $request->getPathInfo();

        if (!$this->pageViewsWithBaseUrl) {
            $baseUrl = $request->getBaseUrl();
            if ($baseUrl != '/') {
                $uri = str_replace($baseUrl, '', $path);
            }
        }

        $params = $request->query->all();
        if (!empty($this->whitelist) && !empty($params)) {
            $whitelist = array_flip($this->whitelist);
            $params = array_intersect_key($params, $whitelist);
        }

        $requestUri = $path;
        $query = http_build_query($params);

        if (isset($query) && '' != trim($query)) {
            $requestUri .= '?'. $query;
        }
        return $requestUri;
    }

    /**
     * @return array $trackers
     */
    public function getTrackers(array $trackerKeys = array())
    {
        $trackers = array();

        if (!empty($trackerKeys)) {
            foreach ($trackerKeys as $key) {
                if (isset($this->trackers[$key])) {
                    $trackers[$key] = $this->trackers[$key];
                }
            }
        } else {
            $trackers = $this->trackers;
        }

        return array_filter($trackers, function($tracker) {
            return !isset($tracker['serverSide']) || !$tracker['serverSide'];
        });
    }

    /**
     * @return array $trackers
     */
    public function getServerTrackers(array $trackerKeys = array())
    {
        $trackers = array();

        if (!empty($trackerKeys)) {
            foreach ($trackerKeys as $key) {
                if (isset($this->trackers[$key])) {
                    $trackers[$key] = $this->trackers[$key];
                }
            }
        } else {
            $trackers = $this->trackers;
        }

        return array_filter($trackers, function($tracker) {
            return isset($tracker['serverSide']) && $tracker['serverSide'];
        });
    }

    /**
     * @param string $trackerKey
     *
     * @return boolean $isTransactionValid
     */
    public function isTransactionValid($trackerKey = null)
    {
        if (!$this->hasTransaction($trackerKey) || (null === $this->getTransactionFromSession($trackerKey)->getOrderNumber())) {
            return false;
        }
        if ($this->hasItems($trackerKey)) {
            $items = $this->getItemsFromSession($trackerKey);
            foreach ($items as $item) {
                if (!$item->getOrderNumber() || !$item->getSku() || !$item->getPrice() || !$item->getQuantity()) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @param string $trackerKey
     *
     * @return Transaction $transaction
     */
    public function getTransaction($trackerKey = null)
    {
        return $this->getOnce(self::TRANSACTION_KEY, $trackerKey);
    }

    /**
     * @param string $trackerKey
     *
     * @return boolean $hasTransaction
     */
    public function hasTransaction($trackerKey = null)
    {
        return $this->has(self::TRANSACTION_KEY, $trackerKey);
    }

    /**
     * @param Transaction $transaction
     * @param string $trackerKey
     */
    public function setTransaction(Transaction $transaction, $trackerKey = null)
    {
        $this->set(self::TRANSACTION_KEY, $transaction, $trackerKey);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param string $trackerKey
     */
    private function add($key, $value, $trackerKey = null)
    {
        if (null !== $trackerKey) {
            $key .= '/' . $trackerKey;
        }

        $bucket = array();

        if (isset($this->currentSession[$key])) {
            $bucket = $this->currentSession[$key];
        }

        $bucket = $this->container->get('session')->get($key, $bucket);
        $bucket[] = $value;

        $this->currentSession[$key] = $bucket;

        $this->container->get('session')->set($key, $bucket);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param string $trackerKey
     */
    private function set($key, $value, $trackerKey = null)
    {
        if (null !== $trackerKey) {
            $key .= '/' . $trackerKey;
        }

        $this->currentSession[$key] = $value;
        $this->container->get('session')->set($key, $value);
    }

    /**
     * @param string $key
     * @param string $trackerKey
     *
     * @return boolean $hasKey
     */
    private function has($key, $trackerKey = null)
    {
        if (null !== $trackerKey) {
            $key .= '/' . $trackerKey;
        }

        $bucket = array();

        if (isset($this->currentSession[$key])) {
            $bucket = $this->currentSession[$key];
        }

        $bucket = $this->container->get('session')->get($key, $bucket);

        return !empty($bucket);
    }

    /**
     * @param string $key
     * @param string $trackerKey
     *
     * @return array $value
     */
    private function get($key, $trackerKey = null)
    {
        if (null !== $trackerKey) {
            $key .= '/' . $trackerKey;
        }

        $value = array();

        if (isset($this->currentSession[$key])) {
            $value = $this->currentSession[$key];
        }

        return $this->container->get('session')->get($key, $value);
    }

    /**
     * @param string $key
     * @param string $trackerKey
     *
     * @return array $value
     */
    private function getOnce($key, $trackerKey = null)
    {
        $value = $this->get($key, $trackerKey);

        if (null !== $trackerKey) {
            $key .= '/' . $trackerKey;
        }

        $this->container->get('session')->remove($key);

        return $value;
    }

    /**
     * @param string $trackerKey
     *
     * @return array $items
     */
    private function getItemsFromSession($trackerKey = null)
    {
        return $this->get(self::ITEMS_KEY, $trackerKey);
    }

    /**
     * @param string $trackerKey
     *
     * @return Transaction $transaction
     */
    private function getTransactionFromSession($trackerKey = null)
    {
        return $this->get(self::TRANSACTION_KEY, $trackerKey);
    }

    /**
     * 
     * @return string
     */
    public function getApiKey()
    {
        return $this->api_key;
    }

    /**
     * 
     * @return string
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * @return string 
     */
    public function getTableId()
    {
        return $this->table_id;
    }

    public function serverSpread($request, $visitor, $trackingSession)
    {
        $trackers = array();

        foreach ($this->getServerTrackers() as $key => $trackerConfig) {
            if ($request->isXmlHttpRequest() && !$this->getTrackAjax($key)) {
                continue;
            }

            $tracker = new Tracker($trackerConfig['accountId'], $trackerConfig['domain']);

            $tracker->setAllowHash($this->getAllowHash($key));

            foreach ($this->getCustomVariables() as $customVariable) {
                $tracker->addCustomVariable(new \UnitedPrototype\GoogleAnalytics\CustomVariable(
                    $customVariable->getIndex(),
                    $customVariable->getName(),
                    $customVariable->getValue(),
                    $customVariable->getScope()
                ));
            }

            $trackers[$key] = $tracker;
        }

        foreach ($this->getPageViewQueue() as $pageView) {
            foreach ($trackers as $tracker) {
                $tracker->trackPageview(new \UnitedPrototype\GoogleAnalytics\Page($pageView), $trackingSession, $visitor);
            }
        }

        if ($this->hasCustomPageView()) {
            foreach ($trackers as $tracker) {
                $tracker->trackPageview(new \UnitedPrototype\GoogleAnalytics\Page($this->getCustomPageView()), $trackingSession, $visitor);
            }
        } else {
            foreach ($trackers as $key => $tracker) {
                if ($this->getCurrentPageTracking($key)) {
                    $tracker->trackPageview(new \UnitedPrototype\GoogleAnalytics\Page($this->getRequestUri($request)), $trackingSession, $visitor);
                }
            }
        }

        if ($this->isTransactionValid()) {
            $transactionConfig = $this->getTransaction();

            $transaction = new \UnitedPrototype\GoogleAnalytics\Transaction();

            $transaction->setAffiliation($transactionConfig->getAffiliation());
            $transaction->setCity($transactionConfig->getCity());
            $transaction->setCountry($transactionConfig->getCountry());
            $transaction->setOrderId($transactionConfig->getOrderNumber());
            $transaction->setShipping($transactionConfig->getShipping());
            $transaction->setTax($transactionConfig->getTax());
            $transaction->setTotal($transactionConfig->getTotal());
            $transaction->setRegion($transactionConfig->getState());

            foreach ($this->getItems() as $itemConfig) {
                $item = new \UnitedPrototype\GoogleAnalytics\Item();

                $item->setName($itemConfig->getName());
                $item->setOrderId($itemConfig->getOrderNumber());
                $item->setPrice($itemConfig->getPrice());
                $item->setQuantity($itemConfig->getQuantity());
                $item->setSku($itemConfig->getSku());
                $item->setVariation($itemConfig->getCategory());

                $transaction->addItem($item);
            }

            foreach ($trackers as $tracker) {
                $tracker->trackTransaction($transaction, $trackingSession, $visitor);
            }
        }

        foreach ($this->getEventQueue() as $eventConfig) {
            $event = new \UnitedPrototype\GoogleAnalytics\Event($eventConfig->getCategory(), $eventConfig->getAction(), $eventConfig->getLabel(), $eventConfig->getValue());

            foreach ($trackers as $tracker) {
                $tracker->trackEvent($event, $trackingSession, $visitor);
            }
        }

        foreach ($trackers as $key => $tracker) {
            foreach ($this->getPageViewQueue($key) as $pageView) {
                $tracker->trackPageview(new \UnitedPrototype\GoogleAnalytics\Page($pageView), $trackingSession, $visitor);
            }

            if ($this->hasCustomPageView($key)) {
                $tracker->trackPageview(new \UnitedPrototype\GoogleAnalytics\Page($this->getCustomPageView()), $trackingSession, $visitor);
            }

            if ($this->isTransactionValid($key)) {
                $transactionConfig = $this->getTransaction($key);

                $transaction = new \UnitedPrototype\GoogleAnalytics\Transaction();

                $transaction->setAffiliation($transactionConfig->getAffiliation());
                $transaction->setCity($transactionConfig->getCity());
                $transaction->setCountry($transactionConfig->getCountry());
                $transaction->setOrderId($transactionConfig->getOrderNumber());
                $transaction->setShipping($transactionConfig->getShipping());
                $transaction->setTax($transactionConfig->getTax());
                $transaction->setTotal($transactionConfig->getTotal());
                $transaction->setRegion($transactionConfig->getState());

                foreach ($this->getItems($key) as $itemConfig) {
                    $item = new \UnitedPrototype\GoogleAnalytics\Item();

                    $item->setName($itemConfig->getName());
                    $item->setOrderId($itemConfig->getOrderNumber());
                    $item->setPrice($itemConfig->getPrice());
                    $item->setQuantity($itemConfig->getQuantity());
                    $item->setSku($itemConfig->getSku());
                    $item->setVariation($itemConfig->getCategory());

                    $transaction->addItem($item);
                }

                $tracker->trackTransaction($transaction, $trackingSession, $visitor);
            }

            foreach ($this->getEventQueue($key) as $eventConfig) {
                $event = new \UnitedPrototype\GoogleAnalytics\Event($eventConfig->getCategory(), $eventConfig->getAction(), $eventConfig->getLabel(), $eventConfig->getValue());

                $tracker->trackEvent($event, $trackingSession, $visitor);
            }
        }
    }
}
