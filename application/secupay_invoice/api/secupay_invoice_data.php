<?php

require_once getShopBasePath() . 'modules/secupay/api/secupay_api.php';

class secupay_invoice_data extends oxUBase {
    static function secupay_get_invoice_data($hash) {
        if (!empty($hash)) {
            $data = array();
            $data['hash'] = $hash;
            $data['apikey'] = oxRegistry::getConfig()->getConfigParam('secupay_api_key');

            $sp_api = new secupay_api($data, 'status');
            $response = $sp_api->request();

            secupay_log::log(true, print_r($response, true));

            if(isset($response->status) && $response->check_response() && isset($response->data->opt)) {
                return $response->data->opt;
            }
        }
        return false;
    }
}