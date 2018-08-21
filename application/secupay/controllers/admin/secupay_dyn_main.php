<?php

class secupay_dyn_main extends oxAdminDetails {

    protected $_sThisTemplate = 'secupay_dyn_main.tpl';

    public function getSecupayVersion() {
        return "2.2.0";
    }

    public function getApiKey() {
        return $this->getConfig()->getConfigParam('secupay_api_key');
    }

    public function getShopVersion() {
        return $this->getConfig()->getActiveShop()->oxshops__oxversion->value . "_" . $this->getConfig()->getRevision();
    }

    public function getModVersion() {
        return $this->getSecupayVersion();
    }

}

