<?php
/**
 * secupay Payment Module
 * @author    secupay AG
 * @copyright 2019, secupay AG
 * @license   LICENSE.txt
 * @category  Payment
 *
 * Description:
 *  Oxid Plugin for integration of secupay AG payment services
 */

class secupay_dyn_main extends oxAdminDetails
{

    /**
     * @var string
     */
    protected $_sThisTemplate = 'secupay_dyn_main.tpl';
    /**
     * @return mixed
     */
    public function getApiKey()
    {
        return $this->getConfig()
                    ->getConfigParam('secupay_api_key');
    }
    /**
     * @return string
     */
    public function getShopVersion()
    {
        return $this->getConfig()
                    ->getActiveShop()->oxshops__oxversion->value . "_" . $this->getConfig()
                                                                              ->getRevision();
    }
    /**
     * @return string
     */
    public function getModVersion()
    {
        return $this->getSecupayVersion();
    }
    /**
     * @return string
     */
    public function getSecupayVersion()
    {
        return "2.2.0";
    }

}

