<?php

namespace Klaviyo\Reclaim\Observer;

use Klaviyo\Reclaim\Helper\Data;
use Klaviyo\Reclaim\Helper\ScopeSetting;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Newsletter\Model\Subscriber;
use Magento\Framework\Event\ManagerInterface;
use Klaviyo\Reclaim\Api\Data\PropertiesInterfaceFactory;

class NewsletterSubscribeObserver implements ObserverInterface
{
    private const SOURCE_ID_MAGENTO2 = '-56';

    /**
     * @var Data
     */
    protected $helper;
    /**
     * @var ScopeSetting
     */
    protected $scopeSetting;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var ManagerInterface
     */
    protected $eventManager;
    /**
     * @var PropertiesInterfaceFactory
     */
    protected $propertiesFactory;


    public function __construct(
        Data $helper,
        ScopeSetting $scopeSetting,
        CustomerRepositoryInterface $customerRepository,
        ManagerInterface $eventManager,
        PropertiesInterfaceFactory $propertiesFactory

    ) {
        $this->helper = $helper;
        $this->scopeSetting = $scopeSetting;
        $this->customerRepository = $customerRepository;
        $this->eventManager = $eventManager;
        $this->propertiesFactory = $propertiesFactory;
    }

    public function execute(Observer $observer)
    {
        if (!$this->scopeSetting->isEnabled()) {
            return;
        }

        /** @var Subscriber $subscriber */
        $subscriber = $observer->getDataObject();
        if ($subscriber && ($subscriber->isStatusChanged() || $subscriber->isObjectNew())) {
            $subscriptionStatus = $subscriber->getStatus();
            $customer = $this->getCustomer($subscriber);

            $properties = $this->propertiesFactory->create();

            $this->eventManager->dispatch(
                'before_subscribe_email_to_klaviyo_list',
                [
                    'customer' => $customer,
                    'subscriber' => $subscriber,
                    'properties' => $properties
                ]
            );
            
            if ($subscriber->getId() && $subscriptionStatus === Subscriber::STATUS_SUBSCRIBED) {
                $customProperties = $properties->getData('properties');
                $this->helper->subscribeEmailToKlaviyoList(
                    $customer ? $customer->getEmail() : $subscriber->getEmail(),
                    $customer ? $customer->getFirstname() : $subscriber->getFirstname(),
                    $customer ? $customer->getLastname() : $subscriber->getLastname(),
                    $subscriber->getStoreId(),
                    $customProperties
                );
            }

            if ($subscriber->getId() && $subscriptionStatus === Subscriber::STATUS_UNSUBSCRIBED) {
                $this->helper->unsubscribeEmailFromKlaviyoList(
                    $customer ? $customer->getEmail() : $subscriber->getEmail(),
                    $subscriber->getStoreId()
                );
            }
        }
    }

    /**
     * @param Subscriber $subscriber
     * @return CustomerInterface|null
     */
    private function getCustomer(Subscriber $subscriber)
    {
        $customer = null;

        if ($subscriber->getCustomerId()) {
            try {
                $customer = $this->customerRepository->getById($subscriber->getCustomerId());
            } catch (NoSuchEntityException $e) {
                // If the customer doesn't exist - return null
            }
        }

        return $customer;
    }
}
