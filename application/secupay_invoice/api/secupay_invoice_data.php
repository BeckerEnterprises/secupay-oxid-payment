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

require_once getShopBasePath() . 'modules/secupay/api/secupay_api.php';

/**
 * Class secupay_invoice_data
 */
class secupay_invoice_data extends oxUBase
{
    /**
     * @param $hash
     *
     * @return bool
     */
    static function secupay_get_invoice_data($hash)
    {
        if (!empty($hash)) {
            $data           = array();
            $data['hash']   = $hash;
            $data['apikey'] = oxRegistry::getConfig()
                                        ->getConfigParam('secupay_api_key');

            $sp_api   = new secupay_api($data, 'status');
            $response = $sp_api->request();

            secupay_log::log(true, print_r($response, true));

            if (isset($response->status) && $response->check_response() && isset($response->data->opt)) {
                return $response->data->opt;
            }
        }
        return false;
    }
}