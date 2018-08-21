<?php

header("content-type:application/json");

require_once dirname(__FILE__) . "/../../../../bootstrap.php";

require_once getShopBasePath() . 'modules/secupay/api/secupay_api.php';


if (!function_exists('isAdmin')) {
    /**
     * Returns false.
     *
     * @return bool
     */
    function isAdmin() {
        return false;
    }
}

$hash = $_REQUEST['hash'];
$apikey = $_REQUEST['apikey'];

$params = array("apikey" => $apikey, "hash" => $hash);

if (is_string($hash) && !empty($hash) && is_string($apikey) && !empty($apikey)) {
    $sp_api = new secupay_api($params, "status");
    $answer = $sp_api->request();
    echo(json_encode($answer->data));

    $oDB = oxDb::getDb();
    $oDB_connection = oxDb::getInstance();

    if ($answer->check_response() && !empty($answer->data->status)) {
        if (!empty($answer->data->trans_id)) {
            secupay_table::setTransactionId($hash, $answer->data->trans_id);
        }

        $status = '';
        $msg = '';

        if (!empty($answer->data->status_description) && is_string($answer->data->status_description) && strlen($answer->data->status_description) > 2) {
            $status = $answer->data->status_description;
        }

        if (!empty($answer->data->payment_status) && is_string($answer->data->payment_status) && strlen($answer->data->payment_status) > 2) {
            $msg = "Status: " . $answer->data->payment_status;
        }

        if(!empty($status) || !empty($msg)) {
            secupay_table::createStatusEntry($hash, $msg, $status);
        }
    }
}