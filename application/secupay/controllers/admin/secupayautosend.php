<?php
require_once getShopBasePath() . 'modules/secupay/api/secupay_api.php';
class secupayautosend extends secupayautosend_parent {
    protected $_secupay_log = false;

    public function render()
    {
        $this->_secupay_log = $this->getConfig()->getConfigParam('secupay_blDebug_log');
        secupay_log::log($this->_secupay_log, "secupayautosend, beginn");
        self::autosend();
        $sTemplate = parent::render();
        return $sTemplate;
    }

    /**
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseErrorException
     */
    public function autosend(){
        $this->_secupay_log = $this->getConfig()->getConfigParam('secupay_blDebug_log');
        $active_autosend = $this->getConfig()->getConfigParam('secupay_invoice_autosend');
        $active_autoinvoice = $this->getConfig()->getConfigParam('secupay_invoice_autoinvoice');
        $active_tautosend = $this->getConfig()->getConfigParam('secupay_tracking_autosend');
        secupay_log::log($this->_secupay_log, "secupayautosend:".print_r($active_autosend,true));
        if($active_autosend or $active_tautosend){
            $oDb = oxDb::getDb();

            $sql="SELECT
               oxorder.OXORDERNR,
               oxorder.OXSENDDATE,
               oxsecupay.`hash`,
               oxorder.OXBILLNR,
               oxorder.OXTRACKCODE,
               oxorder.OXDELTYPE,
               oxsecupay.payment_method
               FROM
               oxorder
               INNER JOIN oxsecupay ON oxsecupay.oxordernr = oxorder.OXORDERNR AND oxsecupay.oxorder_id = oxorder.OXID
               WHERE
               oxsecupay.v_send IS NULL AND oxorder.OXSENDDATE !='0000-00-00 00:00:00' AND oxorder.OXTRACKCODE IS NOT NULL";
            $autosends = $oDb->getAll($sql);
            foreach ($autosends as $autosend){
                $invoice=$autosend['0'];
                if($active_autoinvoice){
                    $invoice=$autosend['3'];
                }
                $daten = array(
                    'apikey' => $this->getConfig()->getConfigParam('secupay_api_key'),
                    'hash' => $autosend['2'],
                    'invoice_number' => $invoice,
                    'tracking' => array(
                        'provider' => self::getCarrier($autosend['4'],$autosend['5']),
                        'number' => $autosend['4']
                    )
                );
                $sp_api = new secupay_api($daten, self::getPayment($autosend['6']));
                $api_return = $sp_api->request();
                if ($api_return->status == 'ok' or utf8_decode($api_return->errors[0]->message) == 'Zahlung konnte nicht abgeschlossen werden'){
                    $vtrack = $api_return->data-trans_id;
                    $sQ = "UPDATE oxsecupay SET v_status = ?, tracking_code = ?, carrier_code = ?, v_send = '1',searchcode = ? WHERE hash = ?";
                    secupay_log::log($this->_secupay_log, "secupayautosend,".print_r($api_return,true));
                    $oDb->execute($sQ, [$vtrack, $autosend['4'], $autosend['5'], $invoice, $autosend['2']]);
                }
            }
        }
    }

    public function getCarrier($trackingnumber,$provider)
     {
        if (
            preg_match("/^1Z\s?[0-9A-Z]{3}\s?[0-9A-Z]{3}\s?[0-9A-Z]{2}\s?[0-9A-Z]{4}\s?[0-9A-Z]{3}\s?[0-9A-Z]$/i", $trackingnumber)) {
                $resprovider = "UPS";
        } elseif(
            preg_match("/^\d{14}$/", $trackingnumber)) {
                $resprovider = "HLG";
        } elseif(
            preg_match("/^\d{11}$/", $trackingnumber)) {
                $resprovider = "GLS";
        } elseif(
            preg_match("/[A-Z]{3}\d{2}\.?\d{2}\.?(\d{3}\s?){3}/", $trackingnumber) || 
            preg_match("/[A-Z]{3}\d{2}\.?\d{2}\.?\d{3}/", $trackingnumber) || 
            preg_match("/(\d{12}|\d{16}|\d{20})/", $trackingnumber)) {
                $resprovider = "DHL";
         } elseif (
            preg_match("/RR\s?\d{4}\s?\d{5}\s?\d(?=DE)/", $trackingnumber) || 
            preg_match("/NN\s?\d{2}\s?\d{3}\s?\d{3}\s?\d(?=DE(\s)?\d{3})/", $trackingnumber) || 
            preg_match("/RA\d{9}(?=DE)/", $trackingnumber) || preg_match("/LX\d{9}(?=DE)/", $trackingnumber) ||
            preg_match("/LX\s?\d{4}\s?\d{4}\s?\d(?=DE)/", $trackingnumber) || 
            preg_match("/LX\s?\d{4}\s?\d{4}\s?\d(?=DE)/", $trackingnumber) || 
            preg_match("/XX\s?\d{2}\s?\d{3}\s?\d{3}\s?\d(?=DE)/", $trackingnumber) || 
            preg_match("/RG\s?\d{2}\s?\d{3}\s?\d{3}\s?\d(?=DE)/", $trackingnumber)) {
                $resprovider = "DPAG";
         } else {
            $resprovider = $provider;
         }
        return $resprovider;
    }

    function getPayment($payment){
        if($payment=='secupay_invoice'){
            return 'capture' ;
        }else{
            return 'adddata'; 
        }
    }
}
