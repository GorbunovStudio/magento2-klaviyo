<?php

namespace Klaviyo\Reclaim\Helper;

use Klaviyo\Reclaim\KlaviyoV3Sdk\KlaviyoV3Api;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    const USER_AGENT = 'Klaviyo/1.0';
    const KLAVIYO_HOST = 'https://a.klaviyo.com/';
    const LIST_V3_API = 'api/list';

    /**
     * Klaviyo logger helper
     * @var \Klaviyo\Reclaim\Helper\Logger $klaviyoLogger
     */
    protected $_klaviyoLogger;

    /**
     * Klaviyo scope setting helper
     * @var \Klaviyo\Reclaim\Helper\ScopeSetting $klaviyoScopeSetting
     */
    protected $_klaviyoScopeSetting;

    /**
     * Variable used for storage of klAddedToCartPayload between observers
     * @var
     */
    private $observerAtcPayload;

    /**
     * V3 API Wrapper
     * @var KlaviyoV3Api $api
     */
    protected $api;

    public function __construct(
        Context $context,
        Logger $klaviyoLogger,
        ScopeSetting $klaviyoScopeSetting
    ) {
        parent::__construct($context);
        $this->_klaviyoLogger = $klaviyoLogger;
        $this->_klaviyoScopeSetting = $klaviyoScopeSetting;
        $this->observerAtcPayload = null;
    }

    public function getObserverAtcPayload()
    {
        return $this->observerAtcPayload;
    }

    public function setObserverAtcPayload($data)
    {
        $this->observerAtcPayload = $data;
    }

    public function unsetObserverAtcPayload()
    {
        $this->observerAtcPayload = null;
    }

    public function getKlaviyoLists()
    {
        try {
            $api = new KlaviyoV3Api($this->_klaviyoScopeSetting->getPublicApiKey(), $this->_klaviyoScopeSetting->getPrivateApiKey(), $this->_klaviyoScopeSetting, $this->_klaviyoLogger);
            $lists_response = $api->getLists();
            $lists = array();

            foreach ($lists_response as $list) {
                $lists[] = array(
                    'id' => $list['id'],
                    'name' => $list['attributes']['name']
                );
            }

            return [
                'success' => true,
                'lists' => $lists
            ];
        } catch (\Exception $e) {
            $this->_klaviyoLogger->log(sprintf('Unable to get list: %s', $e->getMessage()));
            return [
                'success' => false,
                'reason' => $e->getMessage()
            ];
        }
    }

    /**
     * @param string $email
     * @param string|null $firstName
     * @param string|null $lastName
     * @param int|null $storeId
     * @param array|null $properties 
     * @param string|null $listId
     * @return array|false|null|string
     */
    public function subscribeEmailToKlaviyoList($email, $firstName = null, $lastName = null, $storeId = null, $properties = null, $listId = null)
    {
        $resolvedListId = $listId ?: $this->_klaviyoScopeSetting->getNewsletter($storeId);
        $optInSetting = $this->_klaviyoScopeSetting->getOptInSetting($storeId);

        $profileData = [];
        $profileData['email'] = $email;
        if ($firstName) {
            $profileData['first_name'] = $firstName;
        }
        if ($lastName) {
            $profileData['last_name'] = $lastName;
        }
        if ($properties && is_array($properties) && !empty($properties)) {
            $profileData['properties'] = $properties;
        }

        $api = new KlaviyoV3Api(
            $this->_klaviyoScopeSetting->getPublicApiKey($storeId),
            $this->_klaviyoScopeSetting->getPrivateApiKey($storeId),
            $this->_klaviyoScopeSetting,
            $this->_klaviyoLogger
        );

        try {
            if ($optInSetting == ScopeSetting::API_SUBSCRIBE) {
                // Subscribe profile using the profile creation endpoint for lists
                $consent_profile_object = array(
                    'type' => 'profile',
                    'attributes' => array(
                        'email' => $email,
                        'subscriptions' => array(
                            'email' => array(
                                'marketing' => array(
                                    'consent' => 'SUBSCRIBED'
                                )
                            )
                        )
                    )
                );
                $api->subscribeMembersToList($resolvedListId, array($consent_profile_object));
                if ($properties && is_array($properties) && !empty($properties)) {
                    $profile = $api->searchProfileByEmail($email);
                    if ($profile && !empty($profile['profile_id'])) {
                        $api->updateProfile($profile['profile_id'], $firstName, $lastName, $properties);
                    }
                }
            } else {
                // Search for profile by email using the api/profiles endpoint
                $existing_profile = $api->searchProfileByEmail($email);
                if (!$existing_profile) {
                    // If the profile exists, use the ID to add to a list
                    // If the profile does not exist, create
                    $new_profile = $api->createProfile($profileData);
                    $api->addProfileToList($resolvedListId, $new_profile['profile_id']);
                } else {
                    $profile_id = $existing_profile['profile_id'];
                    $api->addProfileToList($resolvedListId, $profile_id);
                    if ($properties && is_array($properties) && !empty($properties)) {
                        $api->updateProfile($profile_id, $firstName, $lastName, $properties);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->_klaviyoLogger->log(sprintf('Unable to subscribe %s to list %s: %s', $email, $resolvedListId, $e));
        }
    }

    /**
     * @param string $email
     * @param int|null $storeId
     * @return array|string|null
     */
    public function unsubscribeEmailFromKlaviyoList($email, $storeId = null)
    {
        $api = new KlaviyoV3Api(
            $this->_klaviyoScopeSetting->getPublicApiKey($storeId),
            $this->_klaviyoScopeSetting->getPrivateApiKey($storeId),
            $this->_klaviyoScopeSetting,
            $this->_klaviyoLogger
        );
        $listId = $this->_klaviyoScopeSetting->getNewsletter($storeId);
        try {
            $response = $api->unsubscribeEmailFromKlaviyoList($email, $listId);
        } catch (\Exception $e) {
            $this->_klaviyoLogger->log(sprintf('Unable to unsubscribe %s from list %s: %s', $email, $listId, $e));
            $response = false;
        }

        return $response;
    }

    public function klaviyoTrackEvent($event, $customer_properties = [], $properties = [], $timestamp = null, $storeId = null)
    {
        if (
            (!array_key_exists('$email', $customer_properties) || empty($customer_properties['$email']))
            && (!array_key_exists('$id', $customer_properties) || empty($customer_properties['$id']))
            && (!array_key_exists('$exchange_id', $customer_properties) || empty($customer_properties['$exchange_id']))
        ) {
            return 'You must identify a user by email or ID.';
        }
        $params = array(
            'event' => $event,
            'properties' => $properties,
            'customer_properties' => $customer_properties
        );

        if (!is_null($timestamp)) {
            $params['time'] = $timestamp;
        }

        $api = new KlaviyoV3Api($this->_klaviyoScopeSetting->getPublicApiKey($storeId), $this->_klaviyoScopeSetting->getPrivateApiKey($storeId), $this->_klaviyoScopeSetting, $this->_klaviyoLogger);
        return $api->track($params);
    }

    /**
     * Proxy method to update a Klaviyo profile with custom properties
     *
     * @param string $id
     * @param string|null $firstName
     * @param string|null $lastName
     * @param array|null $properties
     * @param int|null $storeId
     * @return array
     */
    public function updateProfile($id, $firstName = null, $lastName = null, $properties = null, $storeId = null)
    {
        $api = new KlaviyoV3Api(
            $this->_klaviyoScopeSetting->getPublicApiKey($storeId),
            $this->_klaviyoScopeSetting->getPrivateApiKey($storeId),
            $this->_klaviyoScopeSetting,
            $this->_klaviyoLogger
        );
        return $api->updateProfile($id, $firstName, $lastName, $properties);
    }

    /**
     * Get the external catalog ID for an event. This is used to link events to a specific scoped catalog in Klaviyo, so that
     * profile interest events can be connected to a specific scoped product when building flow audiences.
     *
     * @param int $website_id
     * @param int $store_id
     * @return string
     */
    public function getExternalCatalogIdForEvent($website_id, $store_id)
    {
        return $website_id . '-' . $store_id;
    }

    /**
     * Proxy method to search Klaviyo profile by email
     *
     * @param string $email
     * @param int|null $storeId
     * @return false|mixed
     */
    public function searchProfileByEmail($email, $storeId = null)
    {
        $api = new KlaviyoV3Api(
            $this->_klaviyoScopeSetting->getPublicApiKey($storeId),
            $this->_klaviyoScopeSetting->getPrivateApiKey($storeId),
            $this->_klaviyoScopeSetting,
            $this->_klaviyoLogger
        );
        return $api->searchProfileByEmail($email);
    }

}
