<?php

class OTFL_GoogleTagManager_Helper_Data extends Mage_Core_Helper_Abstract {

    const XML_PATH_ACTIVE                           = 'googletagmanager/googletagmanager/active';
    const XML_PATH_PRICE_TYPE                       = 'googletagmanager/googletagmanager/price_type';
    const XML_PATH_CONTAINER                        = 'googletagmanager/googletagmanager/containerid';
    const XML_PATH_CONTAINER_AMP                    = 'googletagmanager/googletagmanager/containeridamp';
    const XML_PATH_DATALAYER_TRANSACTIONS           = 'googletagmanager/googletagmanager/datalayertransactions';
    const XML_PATH_DATALAYER_TRANSACTIONTYPE        = 'googletagmanager/googletagmanager/datalayertransactiontype';
    const XML_PATH_DATALAYER_TRANSACTIONAFFILIATION = 'googletagmanager/googletagmanager/datalayertransactionaffiliation';
    const XML_PATH_DATALAYER_VISITORS               = 'googletagmanager/googletagmanager/datalayervisitors';
    const XML_PATH_FACEBOOK_GRAPHURL               = 'googletagmanager/facebookconversionsapi/graphurl';
    const XML_PATH_FACEBOOK_ACCESSTOKEN            = 'googletagmanager/facebookconversionsapi/accesstoken';
    const XML_PATH_FACEBOOK_PIXELID                = 'googletagmanager/facebookconversionsapi/pixelid';
    const XML_PATH_FACEBOOK_LOG                    = 'googletagmanager/facebookconversionsapi/log';
    const XML_PATH_FACEBOOK_TESTCODE               = 'googletagmanager/facebookconversionsapi/testcode';

    /**
     * Determine if GTM is ready to use.
     *
     * @return bool
     */
    public function isGoogleTagManagerAvailable() {
        return Mage::getStoreConfig(self::XML_PATH_CONTAINER) && Mage::getStoreConfigFlag(self::XML_PATH_ACTIVE);
    }
    /**
     * Get the GTM Price Type.
     *
     * @return string
     */
    public function getPriceType() {
        return Mage::getStoreConfig(self::XML_PATH_PRICE_TYPE);
    }

    /**
     * Get the GTM container ID.
     *
     * @return string
     */
    public function getContainerId() {
        return Mage::getStoreConfig(self::XML_PATH_CONTAINER);
    }

    /**
     * Get the GTM container ID for AMP.
     *
     * @return string
     */
    public function getContainerIdforAmp() {
        return Mage::getStoreConfig(self::XML_PATH_CONTAINER_AMP);
    }

    /**
     * Add transaction data to the data layer?
     *
     * @return bool
     */
    public function isDataLayerTransactionsEnabled() {
        return Mage::getStoreConfig(self::XML_PATH_DATALAYER_TRANSACTIONS);
    }

    /**
     * Get the transaction type.
     *
     * @return string
     */
    public function getTransactionType() {
        if (!Mage::getStoreConfig(self::XML_PATH_DATALAYER_TRANSACTIONTYPE))
            return '';
        return Mage::getStoreConfig(self::XML_PATH_DATALAYER_TRANSACTIONTYPE);
    }

    /**
     * Get the transaction affiliation.
     *
     * @return string
     */
    public function getTransactionAffiliation() {
        if (!Mage::getStoreConfig(self::XML_PATH_DATALAYER_TRANSACTIONAFFILIATION))
            return '';
        return Mage::getStoreConfig(self::XML_PATH_DATALAYER_TRANSACTIONAFFILIATION);
    }

    /**
     * Add visitor data to the data layer?
     *
     * @return bool
     */
    public function isDataLayerVisitorsEnabled() {
        return Mage::getStoreConfig(self::XML_PATH_DATALAYER_VISITORS);
    }

    /**
     * Facebook Conversions API Graph URL
     *
     * @return string
     */
    public function getFacebookApiGraphUrl() {
        if (!Mage::getStoreConfig(self::XML_PATH_FACEBOOK_GRAPHURL))
            return false;
        return Mage::getStoreConfig(self::XML_PATH_FACEBOOK_GRAPHURL);
    }

    /**
     * Facebook Conversions API Access Token
     *
     * @return string
     */
    public function getFacebookApiAccessToken() {
        if (!Mage::getStoreConfig(self::XML_PATH_FACEBOOK_ACCESSTOKEN))
            return false;
        return Mage::getStoreConfig(self::XML_PATH_FACEBOOK_ACCESSTOKEN);
    }

    /**
     * Facebook Conversions API Pixel ID
     *
     * @return string
     */
    public function getFacebookApiPixelId() {
        if (!Mage::getStoreConfig(self::XML_PATH_FACEBOOK_PIXELID))
            return false;
        return Mage::getStoreConfig(self::XML_PATH_FACEBOOK_PIXELID);
    }

    /**
     * Enable or disable Facebook Conversions API Logging
     *
     * @return bool
     */
    public function isFacebookApiLogEnabled() {
        return Mage::getStoreConfig(self::XML_PATH_FACEBOOK_LOG);
    }

    /**
     * Test Event Code for Facebook Conversions API Logging
     *
     * @return bool
     */
    public function getFacebookApiTestcode() {
        return Mage::getStoreConfig(self::XML_PATH_FACEBOOK_TESTCODE);
    }

}
