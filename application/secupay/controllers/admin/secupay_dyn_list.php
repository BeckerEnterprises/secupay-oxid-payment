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

use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;

require_once getShopBasePath() . 'modules/secupay/api/secupay_api.php';
require_once getShopBasePath() . 'modules/secupay/api/secupayPaymentTypes.php';

/**
 * Class secupay_dyn_list
 */
class secupay_dyn_list extends oxAdminList
{
    /**
     * @var string
     */
    protected $_sThisTemplate = 'secupay_dyn_list.tpl';

    /**
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function init()
    {
        parent::init();

        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(oxdb::FETCH_MODE_ASSOC);

        $apikey = $this->getConfig()
                       ->getConfigParam('secupay_api_key');

        $rows = $oDb->select("SELECT * FROM oxsecupay WHERE updated = 0 AND hash <> ''");
        while ($row = $rows->FetchRow()) {
            $this->fetch_secupay_status($row['hash'], $apikey);
        }
    }
    /**
     * @param $hash
     * @param $apikey
     *
     * @return bool
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function fetch_secupay_status($hash, $apikey)
    {
        if (!is_string($hash) || empty($hash) || !is_string($apikey)) {
            return false;
        }
        $secupay_log = $this->getConfig()
                            ->getConfigParam('secupay_blDebug_log');

        $params = array("apikey" => $apikey, "hash" => $hash);

        $sp_api = new secupay_api($params, "status");
        $answer = $sp_api->request();

        $oDB = oxDb::getDb(true);

        if ($answer && $answer->check_response() && !empty($answer->data->status)) {
            if (!empty($answer->data->status_description) && is_string($answer->data->status_description)
                && strlen(
                    $answer->data->status_description
                ) > 2) {
                $msg = '';
                secupay_table::createStatusEntry($hash, $msg, $answer->data->status_description, $secupay_log);
            }

            $oDB->execute("UPDATE oxsecupay SET updated=1 WHERE hash = ?", [$hash]);

            if (!empty ($answer->data->trans_id)) {
                secupay_table::setTransactionId($hash, $answer->data->trans_id, $secupay_log);
                //$sqlQuery = "update oxsecupay set transaction_id = '" . $oDB_connection->escapeString($answer->data->trans_id) . "' where ISNULL(transaction_id) AND hash = '" . $oDB_connection->escapeString($hash) . "'";
                //$oDB->Execute($sqlQuery);
            }
        } else {
            $oDB->execute("UPDATE oxsecupay SET updated=1 WHERE hash = ?", [$hash]);
        }
    }
    /**
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function render()
    {
        $sReturn = parent::render();

        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(oxdb::FETCH_MODE_ASSOC);

        $apikey = $this->getConfig()
                       ->getConfigParam('secupay_api_key');

        $sSql      = "SELECT * 
            FROM oxsecupay 
            LEFT JOIN oxsecupay_status ON oxsecupay_status.oxsecupay_status_id = oxsecupay.oxsecupay_status_id
            LEFT JOIN oxsecupay_iframe_url ON oxsecupay_iframe_url.iframe_url_id = oxsecupay.iframe_url_id
            WHERE NOT isnull(req_data) ORDER BY rank, created DESC";
        $rows_list = $oDb->select($sSql);

        $ta_list = array();
        while ($row = $rows_list->FetchRow()) {
            if (!empty($row['rank']) && !empty($row['oxsecupay_id']) && $row['rank'] < 99) {
                $sql_query_message = "SELECT 
                CONCAT_WS(' - ',DATE_FORMAT(oxsecupay_status.`timestamp`,'%d.%m.%Y %T'), oxsecupay_status.msg) AS 'message'
                FROM oxsecupay_status
                WHERE oxsecupay_status.oxsecupay_id = " . intval($row['oxsecupay_id']) . " AND IFNULL(oxsecupay_status.msg, '') <> ''
                ORDER BY oxsecupay_status.`timestamp` DESC;";

                $sql_result_message = $oDb->select($sql_query_message);
                $row['msg']         = null;
                while ($message = $sql_result_message->FetchRow()) {
                    $row['msg'] .= $message['message'] . '<br>';
                }
            }

            if (isset($row['action']) && strlen($row['action']) > 5) {
                $row['msg'] = "<span style=cursor:pointer; onclick=\"{$row['action']}\">{$row['msg']}</span>";
            }

            if ($row['payment_method'] == "secupay_creditcard") {
                $partial = ",true";
            } else {
                $partial = "";
            }

            if (isset($row['hash']) && strlen($row['hash']) > 5 && $row['payment_method'] == "secupay_invoice") {
                $capture_action = '<a target="_blank" title="Rechnungskauf als f&auml;llig markieren" href="'
                    . SECUPAY_URL . $row['hash'] . '/capture/' . $apikey
                    . '" class="sp_capture"><span class="ico"></span></a>';
            } else {
                $capture_action = "";
            }

            if (isset($row['payment_method'])) {
                $payment_short_desc = secupayPaymentTypes::getSecupayPaymentShortDesc($row['payment_method']);
                if (isset($payment_short_desc)) {
                    $row['payment_method'] = $payment_short_desc;
                }
            }

            $row['amount'] = sprintf("%01.2f", intval($row['amount'], 10) / 100);//$row['amount']
            //TODO Funktionen anpassen und wieder aktivieren
            //$row['actions']='<a title = "Gutschrift" class="delete" onclick="SP.credit_advice(\''.$row['hash'].'\',\''.$row['amount'].'\''.$partial.');"></a><a title="Status" onclick="SP.status_request(\''.$row['hash'].'\');" class="zoomText"><span class="ico"></span></a>';
            if (isset($row['hash']) && strlen($row['hash']) > 5) {
                $row['actions'] .= '<a title="Status abfragen" onclick="SP.status_request(\'' . $row['hash']
                    . '\');" class="zoomText"><span class="ico"></span></a>';
            }
            $row['actions'] .= $capture_action;
            $row['amount']  .= " ";
            $ta_list[]      = $row;
        }

        $this->_aViewData['splist'] = $ta_list;
        //$apikey=$this->getConfig()->getConfigParam('secupay_api_key');

        $this->_aViewData['spapikey'] = $apikey;

        //$sReturn = parent::render();
        return $sReturn;
    }
    /**
     * @return string
     */
    public function getPath()
    {
        return $this->getConfig()
                    ->getSslShopUrl() . 'modules/secupay/controllers/admin/';
    }
}
