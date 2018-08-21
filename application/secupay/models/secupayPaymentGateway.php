<?php

require_once getShopBasePath() . 'modules/secupay/api/secupay_api.php';
require_once getShopBasePath() . 'modules/secupay/api/secupayPaymentTypes.php';

class secupayPaymentGateway extends secupayPaymentGateway_parent {

    var $_interface;
    var $_mode;
    protected $_secupay_log = false;

    public function executePayment($dAmount, &$oOrder) {
        $this->_secupay_log = $this->getConfig()->getConfigParam('secupay_blDebug_log');
        //$ox_payment_id = $this->getSession()->getInstance()->getBasket()->getPaymentId();
        $ox_payment_id = $this->getSession()->getBasket()->getPaymentId();
        $payment_type = secupayPaymentTypes::getSecupayPaymentType($ox_payment_id);
        secupay_log::log($this->_secupay_log, "secupayPaymentGateway executePayment: " . $payment_type);

        if (!isset($payment_type) || !$payment_type) {
            secupay_log::log($this->_secupay_log, "secupayPaymentGateway executePayment, parent");
            return parent::executePayment($dAmount, $oOrder);
        }

        secupay_log::log($this->_secupay_log, "secupayPaymentGateway executePayment");

        $success = $this->getSession()->getVariable('secupay_success');
        $this->getSession()->deleteVariable('secupay_success');
        
        if(isset($success) && $success === true) {
            secupay_log::log($this->_secupay_log, "secupayPaymentGateway executePayment - success");
            return true;
        }
        secupay_log::log($this->_secupay_log, "secupayPaymentGateway executePayment - failure");
        //TODO Fehlermeldung
        return false;
    }
    
    function object_to_array($var) {
        $result = array();
        $references = array();

        // loop over elements/properties
        foreach ($var as $key => $value) {
            // recursively convert objects
            if (is_object($value) || is_array($value)) {
                // but prevent cycles
                if (!in_array($value, $references)) {
                    $result[$key] = $this->object_to_array($value);
                    $references[] = $value;
                }
            } else {
                // simple values are untouched
                $result[$key] = $value;
            }
        }
        return $result;
    }

}
