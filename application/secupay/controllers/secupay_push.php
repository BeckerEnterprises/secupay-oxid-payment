<?php

require_once getShopBasePath() . 'modules/secupay/api/secupay_api.php';
require_once getShopBasePath() . 'modules/secupay/api/secupayPaymentTypes.php';

class secupay_push extends oxUBase {

    private $_notification = array();
    protected $_secupay_log = false;

    protected function _getParameters() {
        secupay_log::log($this->_secupay_log, http_build_query($_POST));

        $oConfig = oxRegistry::getConfig();

        $referrer = parse_url($_SERVER['HTTP_REFERER']);

        $this->_notification = array(
            'hash' => $oConfig->getRequestParameter('hash'),
            'api_key' => $oConfig->getRequestParameter('apikey'),
            'payment_status' => $oConfig->getRequestParameter('payment_status'),
            'comment' => $oConfig->getRequestParameter('hint'),
            'transaction_status' => $oConfig->getRequestParameter('status_description'),
            'host' => $referrer['host']
        );

        return;
    }

    protected function _validateParameters() {
        $is_valid = false;

        if (isset($this->_notification['hash']) && !empty($this->_notification['hash'])) {
            if (isset($this->_notification['api_key']) && !empty($this->_notification['api_key']) && $this->_notification['api_key'] === $this->getConfig()->getConfigParam('secupay_api_key')) {
                if (isset($this->_notification['payment_status']) && !empty($this->_notification['payment_status'])) {
                    $is_valid = true;
                }
            }
        }
        return $is_valid;
    }

    public function render() {
        $oConfig = oxRegistry::getConfig();
        $this->_secupay_log = $oConfig->getConfigParam('secupay_blDebug_log');

        try {
            $this->_getParameters();
            secupay_log::log($this->_secupay_log, 'secupay_push HOST: ' . SECUPAY_HOST);
            //check referrer
            if (isset($this->_notification['host']) && $this->_notification['host'] == SECUPAY_HOST) {
                if ($this->_validateParameters()) {
                    //check
                    $oOrder = $this->getOrder();

                    if ($this->_notification['payment_status'] == 'accepted' AND $oOrder->oxorder__oxpaid->value == '0000-00-00 00:00:00') {
                        $this->updateTransaction();
                        if (isset($oOrder) && $oOrder) {
                            $payment_type = secupayPaymentTypes::getSecupayPaymentType($oOrder->oxorder__oxpaymenttype);
                            secupay_log::log($this->_secupay_log, 'secupay_push payment_type: ' . $payment_type);
                            //check payment_type
                            if (isset($payment_type) && $payment_type) {
                                $this->setOrderAccepted($oOrder);

                                if (secupayPaymentTypes::isOnAccepted_setOrderPaid($oOrder->oxorder__oxpaymenttype)) {
                                    $this->setOrderPaid($oOrder);
                                }

                                $response = 'ack=Approved';
                            } else {
                                $response = 'ack=Disapproved&error=wrong+payment+type';
                            }
                        } else {
                            $response = 'ack=Disapproved&error=no+matching+order+found+for+hash';
                        }
                    } elseif (($this->_notification['payment_status'] == 'denied')
                            || ($this->_notification['payment_status'] == 'issue')
                            || ($this->_notification['payment_status'] == 'void')
                            || ($this->_notification['payment_status'] == 'authorized')) {
                        $this->updateTransaction($this->_notification['payment_status']);

                        if (isset($oOrder) && $oOrder) {
                            if($this->_notification['payment_status'] !== 'authorized') {
                                $this->setOrderError($oOrder);
                            }
                            $response = 'ack=Approved';
                        } else {
                            $response = 'ack=Disapproved&error=no+matching+order+found+for+hash';
                        }
                    /*} elseif ($this->_notification['payment_status'] == 'authorized') {
                        $this->updateTransaction();*/
                    } else {
                        secupay_log::log($this->_secupay_log, "secupay_push, unsupported payment_status: " . $this->_notification['payment_status']);
                        $response = 'ack=Disapproved&error=payment_status+not+supported';
                    }
                } else {
                    secupay_log::log($this->_secupay_log, "secupay_push, paramter validation failed");
                    $response = 'ack=Disapproved&error=request+invalid+data';
                }
            } else {
                secupay_log::log($this->_secupay_log, "secupay_push, host validation failed for: " . $this->_notification['host']);
                $response = 'ack=Disapproved&error=request+invalid';
            }
            echo $response . '&' . http_build_query($_POST);
        } catch (Exception $e) {
            secupay_log::log($this->_secupay_log, "secupay_push, Exception:", $e->getMessage());
            secupay_log::log($this->_secupay_log, "secupay_push, Exception Trace:", $e->getTraceAsString());
            $response = 'ack=Disapproved&error=unexpected+error';
            echo $response . '&' . http_build_query($_POST);
        }
        //prevent Smarty error
        die();
    }

    private function setOrderPaid($oOrder) {
        secupay_log::log($this->_secupay_log, 'secupay_push setOrderPaid');
        secupay_log::log($this->_secupay_log, "secupay_push, oxpaid before: ", $oOrder->oxorder__oxpaid->value);
        //check if oxpaid is already set
        if($oOrder->oxorder__oxpaid->value == '0000-00-00 00:00:00') {
            $oOrder->assign(array('oxorder__oxpaid' => date('Y-m-d H:i:s')));
            $oOrder->save();
            secupay_log::log($this->_secupay_log, "secupay_push, oxpaid after: ", $oOrder->oxorder__oxpaid->value);
        } else {
            secupay_log::log($this->_secupay_log, "secupay_push, oxpaid was already set");
        }
    }

    private function setOrderError($oOrder) {
        secupay_log::log($this->_secupay_log, 'secupay_push setOrderError');
        $oOrder->oxorder__oxtransstatus = new oxField('ERROR');
        $oOrder->oxorder__oxfolder = new oxField('ORDERFOLDER_PROBLEMS');
        $oOrder->save();
    }

    private function setOrderAccepted($oOrder) {
        secupay_log::log($this->_secupay_log, 'secupay_push setOrderAccepted');
        $oOrder->oxorder__oxtransstatus = new oxField('OK');
        $oOrder->oxorder__oxfolder = new oxField('ORDERFOLDER_NEW');
        $oOrder->save();
    }

    /**
     * @return bool|object
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    private function getOrder() {
        $oDB = oxDb::getDb(true);

        $sSql = "select oxorder_id from oxsecupay where hash = ? AND NOT ISNULL(oxordernr)";
        $oID = $oDB->getOne($sSql, [$this->_notification['hash']]);

        if (!empty($oID)) {
            $oOrder = oxNew('oxorder');
            $oOrder->load($oID);

            $oOrderArticles = $oOrder->getOrderArticles();

            if ( !empty($oOrderArticles) && $oOrderArticles->count() ) {
                return $oOrder;
            }
            secupay_log::log($this->_secupay_log, "secupay_push, getOrder - oOrderArticles empty");
            return false;
        } else {
            secupay_log::log($this->_secupay_log, "secupay_push, getOrder - oID empty");
            return false;
        }
    }

    private function updateTransaction($payment_status = null) {
        $msg = 'secupay Status Push - Status: '. $this->_notification['payment_status'];
        secupay_table::createStatusEntry($this->_notification['hash'], $msg, $this->_notification['transaction_status'], $this->_secupay_log);
        if(!empty($payment_status) && $payment_status === 'void') {
            secupay_table::setTransactionRank($this->_notification['hash'], 99, $this->_secupay_log);
        }
    }
}