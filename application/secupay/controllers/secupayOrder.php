<?php
/**
 * Secupay Order Controller class
 */

require_once getShopBasePath() . 'modules/secupay/api/secupay_api.php';
require_once getShopBasePath() . 'modules/secupay/api/secupayPaymentTypes.php';

/**
 * Class controls secupay payment process
 * It also shows the secupay Iframe
 */
class secupayOrder extends secupayOrder_parent {

    protected $_interface;
    protected $_sPaymentId;
    protected $_kkzg_inactive;
    protected $_secupay_log = false;

    /**
     * Constructor
     */
    public function secupayOrder() {
        $this->_secupay_log = $this->getConfig()->getConfigParam('secupay_blDebug_log');
        secupay_log::log($this->_secupay_log, "secupayOrder, constructor");
    }

    /**
     * Function that returns errorMsg
     */
    public function getErrorMsg() {
        if ($this->_lastError || $_REQUEST['msg']) {
            return "<span style='color:red;'>" . $this->_lastError . $_REQUEST['msg'] . "</span>";
        }
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

    /**
     * Important function that returns next step in payment process, calls parent function
     *
     * @return string iSuccess
     */
    protected function _getNextStep($iSuccess) {
        $payment = $this->getPayment();
        if (isset($payment)) {
            $payment_id = $this->getPayment()->getId();
        }
        secupay_log::log($this->_secupay_log, "secupayOrder, _getNextStep payment_id: ", $payment_id);
        $payment_type = secupayPaymentTypes::getSecupayPaymentType($payment_id);

        //$oOrder = oxNew('oxorder');

        if (isset($payment_type) && $payment_type) {
            secupay_log::log($this->_secupay_log, 'secupayOrder, _getNextStep iSuccess: ' . $iSuccess);
            if ($iSuccess === 'secupayOK') {
                secupay_log::log($this->_secupay_log, 'secupayOK - 1');
                //$this->getSession()->setVariable('secupay_success',true);

                try {
                    $hash = $this->getSession()->getVariable('secupay_hash');

                    $oOrder = oxNew( 'oxorder' );
                    $oBasket  = $this->getSession()->getBasket();
                    $oUser= $this->getUser();

                    $iSuccess = $oOrder->finalizeOrder( $oBasket, $oUser );

                    $hash = $this->getSession()->getVariable('secupay_hash');
                    //update ordernumber in secupay table
                    $this->update_ordernr($hash);

                    // performing special actions after user finishes order (assignment to special user groups)
                    $oUser->onOrderExecute( $oBasket, $iSuccess );

                    // proceeding to next view
                    return $this->_getNextStep( $iSuccess );
                } catch ( oxOutOfStockException $oEx ) {
                    secupay_log::log($this->_secupay_log, "secupayOrder, _getNextStep secupayOK - Exception:", $oEx->getMessage());
                    secupay_log::log($this->_secupay_log, "secupayOrder, _getNextStep secupayOK - Trace:", $oEx->getTraceAsString());
                    if($this->cancelSecupayTransaction($hash, "caused by error: OutOfStock") !== true) {
                        secupay_log::log($this->_secupay_log, "secupayOrder, _getNextStep secupayOK - cancel failed");
                    }
                    oxRegistry::get("oxUtilsView")->addErrorToDisplay( $oEx );
                    $oLang = oxRegistry::getLang();
                    $iSuccess = $oLang->translateString( 'SECUPAY_ORDER_ERROR_MESSAGE' );
                } catch ( oxNoArticleException $oEx ) {
                    secupay_log::log($this->_secupay_log, "secupayOrder, _getNextStep secupayOK - Exception:", $oEx->getMessage());
                    secupay_log::log($this->_secupay_log, "secupayOrder, _getNextStep secupayOK - Trace:", $oEx->getTraceAsString());
                    if($this->cancelSecupayTransaction($hash, "caused by error: NoArticle") !== true) {
                        secupay_log::log($this->_secupay_log, "secupayOrder, _getNextStep secupayOK - cancel failed");
                    }
                    oxRegistry::get("oxUtilsView")->addErrorToDisplay( $oEx );
                    $oLang = oxRegistry::getLang();
                    $iSuccess = $oLang->translateString( 'SECUPAY_ORDER_ERROR_MESSAGE' );
                } catch ( oxArticleInputException $oEx ) {
                    if($this->cancelSecupayTransaction($hash, "caused by error: ArticleInputException") !== true) {
                        secupay_log::log($this->_secupay_log, "secupayOrder, _getNextStep secupayOK - cancel failed");
                    }
                    secupay_log::log($this->_secupay_log, "secupayOrder, _getNextStep secupayOK - Exception:", $oEx->getMessage());
                    secupay_log::log($this->_secupay_log, "secupayOrder, _getNextStep secupayOK - Trace:", $oEx->getTraceAsString());
                    oxRegistry::get("oxUtilsView")->addErrorToDisplay( $oEx );
                    $oLang = oxRegistry::getLang();
                    $iSuccess = $oLang->translateString( 'SECUPAY_ORDER_ERROR_MESSAGE' );
                }
            }
            if ($iSuccess === 'secupayCancel') {
                //$iSuccess = 'Transaktion abgebrochen';
                $oLang = oxRegistry::getLang();
                $iSuccess = $oLang->translateString( 'SECUPAY_PAYMENT_CANCEL_MESSAGE' );
            }
            if ($iSuccess === 'secupayError') {
                //$iSuccess = 'Transaktion fehlgeschlagen';
                $oLang = oxRegistry::getLang();
                $iSuccess = $oLang->translateString( 'SECUPAY_PAYMENT_ERROR_MESSAGE' );
            }
        }

        return parent::_getNextStep($iSuccess);
    }

    /**
     * Function that executes the payment
     */
    public function execute() {
        $payment_id = $this->getPayment()->getId();
        secupay_log::log($this->_secupay_log, "secupayOrder, execute ", $payment_id);
        $payment_type = secupayPaymentTypes::getSecupayPaymentType($payment_id);

        if (isset($payment_type) && $payment_type) {
            secupay_log::log($this->_secupay_log, "secupayOrder, execute 2");

            if (!$this->getSession()->checkSessionChallenge()) {
                return;
            }
            $myConfig = $this->getConfig();

            if (method_exists($this, _validateTermsAndConditions)) {
                //oxid 4.9.x
                if (!$this->_validateTermsAndConditions()) {
                    $this->_blConfirmAGBError = 1;

                    return;
                }
            } else {
                //oxid 4.7.x - 4.8.x
                //$myConfig = $this->getConfig();

                if ( !$myConfig->getRequestParameter( 'ord_agb' ) && $myConfig->getConfigParam( 'blConfirmAGB' ) ) {
                    $this->_blConfirmAGBError = 1;
                    return;
                }
            }

            // for compatibility reasons for a while. will be removed in future
            if ( $myConfig->getRequestParameter( 'ord_custinfo' ) !== null && !$myConfig->getRequestParameter( 'ord_custinfo' ) && $this->isConfirmCustInfoActive() ) {
                $this->_blConfirmCustInfoError =  1;
                return;
            }

            // additional check if we really really have a user now
            if ( !$oUser= $this->getUser() ) {
                return 'user';
            }

            // get basket contents
            $oBasket  = $this->getSession()->getBasket();
            if ( $oBasket->getProductsCount() ) {
                try {
                    $oOrder = oxNew( 'oxorder' );
                    $oID = $oBasket->getOrderId();
                    secupay_log::log($this->_secupay_log, "secupayOrder, execute - oID: ", $oID);

                    $oOrder->load($oID);

                    if ( $iOrderState = $oOrder->validateOrder( $oBasket, $oUser ) ) {
                        return $iOrderState;
                    }

                    $iSuccess = $this->createSecupayTransaction($oOrder);

                    return $this->_getNextStep($iSuccess);

                } catch ( oxOutOfStockException $oEx ) {
                    oxRegistry::get("oxUtilsView")->addErrorToDisplay( $oEx );
                } catch ( oxNoArticleException $oEx ) {
                    oxRegistry::get("oxUtilsView")->addErrorToDisplay( $oEx );
                } catch ( oxArticleInputException $oEx ) {
                    oxRegistry::get("oxUtilsView")->addErrorToDisplay( $oEx );
                }
            }

        } else {
            return parent::execute();
        }
    }

    /**
     * TODO check usage
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public function get_hash() {
        $oID = $this->getOrderId();
        secupay_log::log($this->_secupay_log, 'secupayOrder, get_hash, oID: ', $oID);
        if (isset($oID) && !empty($oID)) {
            $oDb = oxDb::getDb();

            $sglQuery = "SELECT hash FROM oxsecupay WHERE oxorder_id = ? Order By created DESC LIMIT 1";
            $_hash = $oDb->getOne($sglQuery, [$oID]);
            secupay_log::log($this->_secupay_log, 'secupayOrder, get_hash, hash: ', $_hash);

            return $_hash;
        } else {
            return false;
        }
    }

    /**
     * Function to which user is redirected from iframe if payment is successfull
     */
    public function processSecupaySuccess() {
        secupay_log::log($this->_secupay_log, "secupayOrder, processSecupaySuccess");
        $hash = $this->getSession()->getVariable('secupay_hash');
        //$this->getSession()->deleteVariable('secupay_hash');
        $this->getSession()->deleteVariable('secupay_iframe_url');
        $this->_sThisTemplate = '/page/checkout/order.tpl';
        secupay_log::log($this->_secupay_log, "secupayOrder, processSecupaySuccess hash: " . $hash);

        if(isset($hash) && $this->validateSecupayTransaction($hash)) {
            $iSuccess = 'secupayOK';
            $this->getSession()->setVariable('secupay_success',true);
        } else {
            $this->getSession()->setVariable('secupay_success',false);
            $iSuccess = 'secupayError';
        }

        return $this->_getNextStep($iSuccess);
    }

    /**
     * Function to which user is redirected from iframe if payment failed
     */
    public function processSecupayFailure() {
        $this->getSession()->deleteVariable('secupay_iframe_url');
        $this->_sThisTemplate = '/page/checkout/order.tpl';
        $iSuccess = 'secupayCancel';
        $this->getSession()->setVariable('secupay_success',false);
        return $this->_getNextStep($iSuccess);
    }

    /**
     * This function displays secupay Iframe for current session
     */
    public function processSecupayIframe() {
        secupay_log::log($this->_secupay_log, "secupayOrder, processSecupayIframe");
        // change current template to template that displays iframe
        $this->_sThisTemplate = 'payment_secupay_iframe.tpl';
        return;
    }

    /**
     * Function that returns the Iframe Url for current session
     * Function has to be public, or the template will not be able to call it
     *
     * @return string IframeUrl
     */
    public function getIframeUrl() {
        // get iframe url from session
        $iframe_url = $this->getSession()->getVariable('secupay_iframe_url');
        if (empty($iframe_url)) {
            oxRegistry::getUtils()->redirect( oxRegistry::getConfig()->getShopHomeURL() . 'cl=order&fnc=processSecupayFailure', true, 302);
            return;
        }
        return $iframe_url;
    }

    protected function getOrderId() {
        $mySession = $this->getSession();
        $oBasket = $mySession->getBasket();
        return $oBasket->getOrderId();
    }

    protected function getBasketAmount() {
        $mySession = $this->getSession();
        $oBasket = $mySession->getBasket();
        return intval(strval(($oBasket->getPrice()->getBruttoPrice() * 100)));
    }

    /**
     * @param $hash
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    protected function update_ordernr($hash) {
        $oID = $this->getOrderId();
        $oOrder = oxNew('oxorder');
        $oOrder->load($oID);
        $oOrdernr = $oOrder->oxorder__oxordernr->value;
        secupay_log::log($this->_secupay_log, "secupayOrder, update_ordernr: " . $oOrdernr . " for hash " . $hash);

        if (is_numeric($oOrdernr) && !empty($hash)) {
            $oDb = oxDb::getDb();

            $oDb->execute("UPDATE oxsecupay SET oxordernr = ? WHERE hash = ?", [$oOrdernr, $hash]);
        }
    }

    public function should_secupay_warn_delivery() {
        $payment = $this->getPayment();
        if (isset($payment)) {
            $payment_id = $this->getPayment()->getId();
            $delivery_adress_option_name = secupayPaymentTypes::getSecupayCheckDeliveryOptionName($payment_id);
            if ($delivery_adress_option_name && $this->getConfig()->getConfigParam($delivery_adress_option_name) == 2) {
                $oBasket = $this->getSession()->getBasket();
                $oID = $oBasket->getOrderId();
                $oOrder = oxnew("oxOrder");
                $oOrder->load($oID);
                $oDelAd = $oOrder->getDelAddressInfo();

                if ($oDelAd) {
                    return true;
                }
            }
        }
        return false;
    }

    protected function validateSecupayTransaction($hash) {
        secupay_log::log($this->_secupay_log, "secupayOrder, validateSecupayTransaction");
        secupay_log::log($this->_secupay_log, "secupayOrder, validateSecupayTransaction hash: " . $hash);
        if (isset($hash)) {
            $daten = array(
                'apikey' => $this->getConfig()->getConfigParam('secupay_api_key'),
                'hash' => $hash,
                'apiversion' => secupay_api::get_api_version()
            );
            $sp_api = new secupay_api($daten, 'status/' . $hash);
            $api_return = $sp_api->request();

            $amount_received = $api_return->data->amount;
            $amount_basket = $this->getBasketAmount();
            $payment_id = $this->getPayment()->getId();
            $payment_type = secupayPaymentTypes::getSecupayPaymentType($payment_id);
            if ($api_return && $api_return->check_response() && isset($api_return->data->status)
                    && ($api_return->data->status == 'accepted' || $api_return->data->status == 'authorized' ||($payment_type=='prepay' && $api_return->data->status == 'scored'))
                    && isset($amount_received) && isset($amount_basket) && intval(strval($amount_received)) === intval(strval($amount_basket))) {

                if(!empty($api_return->data->trans_id) & $api_return->data->trans_id > 0) {
                    secupay_table::setTransactionId($hash, $api_return->data->trans_id, $this->_secupay_log);
                }

                return true;
            } else {
                secupay_log::log($this->_secupay_log, "secupayOrder, validateSecupayTransaction failure!");
                secupay_log::log($this->_secupay_log, "hash status " . $api_return->data->status);
                secupay_log::log($this->_secupay_log, "amount_basket: " . $amount_basket . " amount_received " . $amount_received);

                if(isset($amount_received) && isset($amount_basket) && intval(strval($amount_received)) === intval(strval($amount_basket))) {
                    secupay_log::log($this->_secupay_log, "amount_check true");
                } else {
                    secupay_log::log($this->_secupay_log, "amount_check false");
                }
                //TA ist gegebenenfalls bereits abgeschlossen im secupay system!
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @param $oOrder
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    protected function createSecupayTransaction($oOrder) {
        $this->_secupay_log = $this->getConfig()->getConfigParam('secupay_blDebug_log');
        $ox_payment_id = $this->getSession()->getBasket()->getPaymentId();
        $payment_type = secupayPaymentTypes::getSecupayPaymentType($ox_payment_id);
        secupay_log::log($this->_secupay_log, "secupayOrder createSecupayTransaction: " . $payment_type);

        $this->getSession()->deleteVariable('sp_err_msg');

        secupay_log::log($this->_secupay_log, "secupayOrder createSecupayTransaction");

        $sUserID = $this->getSession()->getVariable("usr");
        $oUser = oxNew("oxuser", "core");
        $oUser->Load($sUserID);
        $mySession = $this->getSession();

        $oBasket = $mySession->getBasket();
        $this->getSession()->deleteVariable('secupay_iframe_url');
        $this->getSession()->deleteVariable('secupay_hash');
        $this->getSession()->deleteVariable('secupay_success');
        $oDB = oxDb::GetDB();
        $oID = $oOrder->getId();
        //if oID is empty, use session value
        if(empty($oID)) {
            $sGetChallenge = $this->getSession()->getVariable( 'sess_challenge' );
            $oID = $sGetChallenge;
            secupay_log::log($this->_secupay_log, "secupayOrder, get oID from Session: ", $oID);
        }

        $active_experience = $this->getConfig()->getConfigParam('secupay_experience');
        secupay_log::log($this->_secupay_log, 'oID: ', $oID);

        $pmnt_id = $oBasket->getPaymentId(); // definiert Zahlart

        $modus = $this->getConfig()->getConfigParam('secupay_blMode');

        if (!oxRegistry::getConfig()->isSSL()) {
            $uri_prefix = oxRegistry::getConfig()->getConfigParam('sShopURL');
        } else {
            $uri_prefix = oxRegistry::getConfig()->getConfigParam('sSSLShopURL');
        }

        $oLang = oxRegistry::getLang();
        $iLang = $oLang->getTplLanguage();

        if (!isset($iLang)) {
            $iLang = $oLang->getBaseLanguage();
            if (!isset($iLang)) {
                $iLang = 0;
            }
        }
        try {
            $sTranslation = $oLang->translateString($oUser->oxuser__oxsal->value, $iLang, isAdmin());
        } catch (oxLanguageException $oEx) {
            // is thrown in debug mode and has to be caught here, as smarty hangs otherwise!
        }

        $daten['apikey'] = $this->getConfig()->getConfigParam('secupay_api_key');
        $daten['payment_type'] = $payment_type;
        if (!$modus) {
            $daten['demo'] = 1;
        }
        $daten['url_success'] = $this->getConfig()->getSslShopUrl() . 'index.php?cl=order&fnc=processSecupaySuccess&sDeliveryAddressMD5='.order::getDeliveryAddressMD5();
        $daten['url_failure'] = $this->getConfig()->getSslShopUrl() . 'index.php?cl=order&fnc=processSecupayFailure';
        $daten['url_push'] = $this->getConfig()->getSslShopUrl() . 'index.php?cl=secupay_push';

        $daten['shop'] = "oxid ce";
        $daten['shopversion'] = $this->getConfig()->getActiveShop()->oxshops__oxversion->value . "_" . $this->getConfig()->getRevision();
        $daten['modulversion'] = "2.2.0";

        $lang_abbr = $oLang->getLanguageAbbr($iLang);
        if(isset($lang_abbr) && $lang_abbr === 'en') {
            $daten['language'] = 'en_US';
        } else if(isset($lang_abbr) && $lang_abbr === 'de') {
            $daten['language'] = 'de_DE';
        }
        $positiv=0;
        $negativ=0;
        if($active_experience){
            $oxiduser=$oUser->oxuser__oxid->value;
            $respositiv=$oDB->getOne("SELECT
                count(oxorder.OXUSERID)
                FROM
                oxorder
                WHERE
                oxorder.OXPAID != '0000-00-00 00:00:00' AND
                oxorder.OXTRACKCODE IS NOT NULL AND
                oxorder.OXUSERID = ?", [$oxiduser]);

            $resnegativ=$oDB->getOne("SELECT
                count(oxorder.OXUSERID)
                FROM
                oxorder
                WHERE
                oxorder.OXUSERID = ?", [$oxiduser]);
            $resnegativ=$resnegativ-$respositiv;
        if($respositiv){
          $positiv=$respositiv;
          $negativ=$resnegativ;  
        }
       }
        $daten['experience']['positive']=$positiv;
        $daten['experience']['negative']=$negativ;
        $daten['title'] = $sTranslation;
        $daten['firstname'] = $oUser->oxuser__oxfname->value;
        $daten['lastname'] = $oUser->oxuser__oxlname->value;
        $daten['company'] = $oUser->oxuser__oxcompany->value;
        $daten['name_affix'] = $oUser->oxuser__oxaddinfo->value;
        $daten['street'] = $oUser->oxuser__oxstreet->value;
        $daten['housenumber'] = $oUser->oxuser__oxstreetnr->value;
        $daten['zip'] = $oUser->oxuser__oxzip->value;
        $daten['city'] = $oUser->oxuser__oxcity->value;
        $sCountryId = $oUser->oxuser__oxcountryid->value;
        $daten['country'] = $oDB->getOne("SELECT oxisoalpha2 FROM oxcountry WHERE oxid = ?", [$sCountryId]);
        $daten['telephone'] = $oUser->oxuser__oxfon->value;
        $daten['dob'] = $oUser->oxuser__oxbirthdate->value;
        $daten['email'] = $oUser->oxuser__oxusername->value;
        $daten['ip'] = $_SERVER['REMOTE_ADDR'];

        $daten['amount'] = intval(strval($oBasket->getPrice()->getBruttoPrice() * 100));
        $daten['currency'] = $oBasket->getBasketCurrency()->name;
        $daten['purpose'] = $this->get_purpose();

        $basketcontents = $oBasket->getContents();
        foreach ($basketcontents as $item) {
            $product = new stdClass();
            $product->article_number = $item->getArticle()->getFieldData('oxartnum');
            $product->name = $item->getTitle();
            $product->model = $item->getArticle()->getFieldData('oxshortdesc');
            $product->ean = $item->getArticle()->getFieldData('oxean');
            $product->quantity = $item->getAmount();
            $product->price = $item->getArticle()->getPrice(1)->getBruttoPrice() * 100;
            $product->total = $item->getPrice()->getBruttoPrice() * 100;
            $product->tax = $item->getPrice()->getVat();
            $products[] = $product;
        }
        $daten['basket'] = json_encode( utf8_ensure($products));

    $oDelAd=$oOrder->getDelAddressInfo();
    if($oDelAd) {
            $delivery_address = new stdClass();
            $delivery_address->firstname = $oDelAd->oxaddress__oxfname->value;
            $delivery_address->lastname = $oDelAd->oxaddress__oxlname->value;
            $delivery_address->street = $oDelAd->oxaddress__oxstreet->value;
            $delivery_address->housenumber = $oDelAd->oxaddress__oxstreetnr->value;
            $delivery_address->zip = $oDelAd->oxaddress__oxzip->value;
            $delivery_address->city = $oDelAd->oxaddress__oxcity->value;
            $sDelCountry = $oDelAd->oxaddress__oxcountryid->value;
            $delivery_address->country = $oDB->getOne("SELECT oxisoalpha2 FROM oxcountry WHERE oxid = ?", [$sDelCountry]);
            $delivery_address->company = $oDelAd->oxaddress__oxcompany->value;
            $daten['delivery_address'] = $delivery_address;
        } else {
            $delivery_address = new stdClass();
            $delivery_address->firstname = $daten['firstname'];
            $delivery_address->lastname = $daten['lastname'];
            $delivery_address->street = $daten['street'];
            $delivery_address->housenumber = $daten['housenumber'];
            $delivery_address->zip = $daten['zip'];
            $delivery_address->city = $daten['city'];
            $delivery_address->country = $daten['country'];
            $delivery_address->company = $daten['company'];
            $daten['delivery_address'] = $delivery_address;
        }

        $daten['apiversion'] = secupay_api::get_api_version();
        if(!empty($oID)) {
            $userfields = new stdClass();
            $userfields->oxorder_id = $oID;
            $daten['userfields'] = $userfields;
        }

        secupay_log::log($this->_secupay_log, "secupayOrder, api request data: ", json_encode(utf8_ensure($daten)));

        $sp_api = new secupay_api($daten, "init");

        $api_return = $sp_api->request();

        $_req_data = addslashes(json_encode(utf8_ensure($daten)));
        $_ret_data = addslashes(json_encode(utf8_ensure($api_return)));

        secupay_log::log($this->_secupay_log, "secupayOrder, api return data: ", $_ret_data);

        if ($api_return->get_hash()) {
            $_hash = $api_return->get_hash();
        } else {
            $_hash = null;
        }

        $status = $api_return->get_status();
        $msg = addslashes($api_return->get_error_message());
        $amount = $daten['amount'];

        if ($api_return && $api_return->check_response()) {
            /* Eintrag in secupay eigene Log Tabelle im Administrationsbereich des Modul */
            secupay_table::createTransactionEntry($_req_data, $_ret_data, $_hash, $ox_payment_id, $oID, $amount, $api_return->get_iframe_url(), $this->_secupay_log);
            secupay_table::createStatusEntry($_hash, '', 'erstellt', $this->_secupay_log);

            $this->getSession()->setVariable('secupay_iframe_url',$api_return->get_iframe_url());
            $this->getSession()->setVariable('secupay_hash',$_hash);

            secupay_log::log($this->_secupay_log, "secupayOrder, Anfrage hash {$_hash} erfolgreich");

            oxRegistry::getUtils()->redirect( oxRegistry::getConfig()->getShopSecureHomeUrl() . 'cl=order&fnc=processSecupayIframe', true, 302);

        } else {
            secupay_table::createTransactionEntry($_req_data, $_ret_data, $_hash, $ox_payment_id, $oID, $amount, $api_return->get_iframe_url(), $this->_secupay_log);
            secupay_table::createStatusEntry($_hash, $msg, $status, $this->_secupay_log);

            secupay_log::log($this->_secupay_log, "secupayOrder, hash {$_hash} fehlgeschlagen");

            $error_message = $api_return->get_error_message_user();

            if(empty($error_message)) {
                $error_message = 'Verbindungsfehler';
            }

            $this->getSession()->setVariable('sp_err_msg', $error_message);

            return $error_message;
        }
    }

    protected function get_purpose() {
        $myConfig = $this->getConfig();
        $_shop = $myConfig->getActiveShop()->oxshops__oxname->getRawValue();
        $_custom_name = $this->getConfig()->getConfigParam('secupay_shopname_ls');
        if (strlen($_custom_name) > 2) {
            $_shop = $_custom_name;
        }
        $purpose = $_shop . "|" . date("d.m.y H:i", oxRegistry::get("oxUtilsDate")->getTime()) . "|Bei Fragen TEL 035955755055";
        secupay_log::log($this->_secupay_log, "secupayOrder, get_purpose:" . $purpose);
        return $purpose;
    }

    protected function cancelSecupayTransaction($hash, $comment = NULL) {
        if(isset($hash) && !empty($hash)) {
            secupay_log::log($this->_secupay_log, "secupayOrder, cancelSecupayTransaction:" . $hash);

            $daten['apikey'] = $this->getConfig()->getConfigParam('secupay_api_key');

            if(isset($comment) && !empty($comment)) {
                $daten['comment'] = $comment;
            }

            $sp_api = new secupay_api($daten, $hash."/cancel");

            $api_return = $sp_api->request();

            $_ret_data = addslashes(json_encode(utf8_ensure($api_return)));

            secupay_log::log($this->_secupay_log, "secupayOrder, cancelSecupayTransaction - api return data: ", $_ret_data);

            $status = $api_return->get_status();
            $msg = addslashes($api_return->get_error_message());

            if ($api_return && $api_return->check_response()) {
                secupay_log::log($this->_secupay_log, "secupayOrder, cancelSecupayTransaction - ok");
                secupay_table::createStatusEntry($hash, 'automat. Storno - ' . $status, '', $this->_secupay_log);
                return true;
            } else {
                secupay_log::log($this->_secupay_log, "secupayOrder, cancelSecupayTransaction - failed");
                secupay_table::createStatusEntry($hash, 'automat. Storno - ' . $status . ' - ' . $msg, '', $this->_secupay_log);
                //show transaction at top of list
                secupay_table::setTransactionRank($hash, 80);
                return false;
            }

        } else {
            secupay_log::log($this->_secupay_log, "secupayOrder, cancelSecupayTransaction - empty hash");
            return false;
        }
    }
}
