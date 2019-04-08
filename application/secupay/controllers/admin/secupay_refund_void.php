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

header("content-type:application/json");

require_once dirname(__FILE__) . "/../../../../bootstrap.php";

require_once getShopBasePath() . 'modules/secupay/api/secupay_api.php';

if (!function_exists('isAdmin')) {
    /**
     * @return bool
     */
    function isAdmin()
    {
        return false;
    }
}

$params = array("apikey" => $_REQUEST['apikey'], "amount" => $_REQUEST['amount'] * 100, "hash" => $_REQUEST['hash']);

$sp_api = new secupay_api($params, "refund");
$answer = $sp_api->request();

echo(json_encode($answer));

if ($answer && $answer->check_response() && !empty ($answer->data->status)) {
    secupay_table::createStatusEntry($_REQUEST['hash'], 'refund: ' . $answer->data->status, '');
}

