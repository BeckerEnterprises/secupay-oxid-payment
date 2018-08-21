<?php

require_once getShopBasePath() . 'modules/secupay/api/secupay_api.php';
require_once getShopBasePath() . 'modules/secupay/api/secupayPaymentTypes.php';

/**
 * Description of secupayPayment
 *
 * @author leubner
 */
class secupayPayment extends secupayPayment_parent {

    //var $secupay_payment_types_active;
    var $payment_types_active;
    protected $_secupay_log = false;

    public function init() {
        $this->_secupay_log = $this->getConfig()->getConfigParam('secupay_blDebug_log');
        $this->getSecupayPaymentTypes();

        $this->_sThisTemplate = parent::render();
        parent::init();
    }

    /*
      public function isKK() {
      return $this->getConfig()->getConfigParam('secupay_creditcard_active');
      }

      public function isLS() {
      return $this->getConfig()->getConfigParam('secupay_debit_active');
      }
     */

    public function getDynValue() {
        return parent::getDynValue();
    }

    public function getDeliveryAdress($pmnttype) {
        /*
          $oBasket = $this->getSession()->getBasket();
          $oID = $oBasket->getOrderId();
          $oOrder = oxnew("oxOrder");
          $oOrder->load($oID);
          $oDelAd = $oOrder->getDelAddressInfo();

          if ($oDelAd && $this->getConfig()->getConfigParam('secupay_blLieferadresse')) {


          if($pmnttype == 1){
          if(!$this->getConfig()->getConfigParam('secupay_blLastschriftZGActive')){
          return true;
          }
          }
          if($pmnttype == 2){
          if(!$this->getConfig()->getConfigParam('secupay_blKreditkarteZGActive')){
          return true;
          }
          }

          return false;
          } else {
          return false;
          }
         */
        return false;
    }

    /**
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public function getSecupayPaymentTypes() {
        $this->payment_types_active = array();

        $oDB = oxDb::getDb(true);
        $sSql = "SELECT OXID FROM oxpayments WHERE oxactive = 1";
        $active_payment_ids = $oDB->getAll($sSql);

        if (!empty($active_payment_ids)) {
            $payment_types = array();
            foreach ($active_payment_ids as $payment_id) {
                $payment_type = secupayPaymentTypes::getSecupayPaymentType($payment_id[0]);
                if (isset($payment_type) && $payment_type) {
                    $payment_types[] = $payment_type;
                }
            }
            $this->payment_types_active = $payment_types;
        }
    }

    public function isSecupayPaymentType($payment_id) {
        $payment_type = secupayPaymentTypes::getSecupayPaymentType($payment_id);
        if (isset($payment_type) && $payment_type) {
            //return in_array($payment_type, $this->secupay_payment_types_active);
            return in_array($payment_type, $this->payment_types_active);
        } else {
            return false;
        }
    }

    public function isSecupayPaymentTypeActive($payment_id) {
        $option_name = secupayPaymentTypes::getSecupayPaymentOptionName($payment_id);
        if ($option_name) {
            if ($this->getConfig()->getConfigParam($option_name)) {
                $delivery_adress_option_name = secupayPaymentTypes::getSecupayCheckDeliveryOptionName($payment_id);
                if ($delivery_adress_option_name && $this->getConfig()->getConfigParam($delivery_adress_option_name) == 1 && $this->isSecupayDeliveryAdressDifferent()) {
                    return false;
                } else {
                    return true;
                }
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getPaymentErrorText() {
        if (!$this->isSecupayPaymentType($this->getCheckedPaymentId())) {
            return parent::getPaymentErrorText();
        }
        $oConfig = oxRegistry::getConfig();
        $session_err = $this->getSession()->getVariable('sp_err_msg');
        $_payerror = $oConfig->getRequestParameter('payerror');
        $req_err = $oConfig->getRequestParameter('payerrortext');

        if (isset($_payerror) && strlen($req_err) > 3) {
            return $req_err;
        }
        if (isset($session_err)) {
            $this->getSession()->deleteVariable('sp_err_msg');
            return $session_err;
        }
        if (empty($req_err)) {
            $req_err = "unknown error";
        }
        return $req_err;
    }

    public function isSecupayDeliveryAdressDifferent() {
        $oBasket = $this->getSession()->getBasket();
        $oID = $oBasket->getOrderId();
        $oOrder = oxnew("oxOrder");
        $oOrder->load($oID);
        $oDelAd = $oOrder->getDelAddressInfo();
        
        if($oDelAd) {
            return true;
        }
        return false;
    }
}
