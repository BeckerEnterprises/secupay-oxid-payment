<?php

require_once getShopBasePath() . 'modules/secupay/api/secupay_api.php';
require_once getShopBasePath() . 'modules/secupay/api/secupayPaymentTypes.php';

class secupay_events extends oxUBase {

    static $secupay_log = true;
    static $secupay_table_names = Array(
        'oxsecupay',
        'oxsecupay_iframe_url',
        'oxsecupay_status'
    );
    static $secupay_oxsecupay_coulmn_names = 
        "'oxsecupay_id',
        'req_data',
        'ret_data',
        'payment_method',
        'transaction_id',
        'oxordernr',
        'oxorder_id',
        'oxsecupay_status_id',
        'rank',
        'amount',
        'action',
        'iframe_url_id',
        'updated',
        'v_send',
        'v_status',
        'searchcode',
        'tracking_code',
        'carrier_code',
        'hash',
        'created',
        'timestamp'";
    static $secupay_oxsecupay_iframe_url_coulmn_names = 
        "'iframe_url_id',
        'iframe_url'";
    static $secupay_oxsecupay_status_coulmn_names = 
        "'oxsecupay_status_id',
        'oxsecupay_id',
        'msg',
        'status',
        'timestamp'";

    static $secupay_payment_types_active = array();

    static function onActivate() {
        secupay_log::log(self::$secupay_log, "secupay_events::onActivate()");
        $payment_types = secupayPaymentTypes::$secupay_payment_types;
        self::requestAvailablePaymenttypes();
        $payment_types_active = self::$secupay_payment_types_active;
        foreach ($payment_types as $payment_type) {
            self::checkPayment($payment_type['payment_id']);
            if (isset($payment_types_active) && in_array($payment_type['payment_type'], $payment_types_active)) {
                self::activatePayment($payment_type['payment_id'], 1);
            }
        }
        self::checkTableStructure();
    }

    static function onDeactivate() {
        secupay_log::log(self::$secupay_log, "secupay_events::onDeactivate()");
        $payment_types = secupayPaymentTypes::$secupay_payment_types;
        foreach ($payment_types as $payment_type) {
            self::activatePayment($payment_type['payment_id'], 0);
        }
    }

    private static function checkPayment($payment_id) {
        try {
            $oDB = oxDb::getDb(true);

            $payment_id_exists = $oDB->getOne("SELECT oxid FROM oxpayments WHERE oxid = ?", [$payment_id]);

            if (isset($payment_id_exists) && !$payment_id_exists) {
                self::createPayment($payment_id);
            }
        } catch (Exception $e) {
            secupay_log::log(self::$secupay_log, "secupay_events, Exception:", $e->getMessage());
            secupay_log::log(self::$secupay_log, "secupay_events, Exception Trace:", $e->getTraceAsString());
        }
    }

    private static function activatePayment($payment_id, $active = 1) {
        try {
            $oDB = oxDb::getDb(true);

            $oDB->execute("UPDATE oxpayments SET oxactive = ? WHERE oxid = ?", [$active, $payment_id]);
        } catch (Exception $e) {
            secupay_log::log(self::$secupay_log, "secupay_events, Exception:", $e->getMessage());
            secupay_log::log(self::$secupay_log, "secupay_events, Exception Trace:", $e->getTraceAsString());
        }
    }

    private static function createPayment($payment_id) {
        try {
            $desc = secupayPaymentTypes::getSecupayPaymentDesc($payment_id);

            if (isset($desc) && $desc) {
                $oDB = oxDb::getDb(true);
                $sSql = "INSERT INTO oxpayments (
                    `OXID`, `OXACTIVE`, `OXDESC`, `OXADDSUM`, `OXADDSUMTYPE`, `OXFROMBONI`, `OXFROMAMOUNT`, `OXTOAMOUNT`,
                    `OXVALDESC`, `OXCHECKED`, `OXDESC_1`, `OXVALDESC_1`, `OXDESC_2`, `OXVALDESC_2`,
                    `OXDESC_3`, `OXVALDESC_3`, `OXLONGDESC`, `OXLONGDESC_1`, `OXLONGDESC_2`, `OXLONGDESC_3`, `OXSORT`
                ) VALUES (
                    ?, 1, ?, 0, 'abs', 0, 0, 1000000, '', 0, ?, '', '', '', '', '', '', '', '', '', 0
                )";

                $oDB->execute($sSql, [$payment_id, $desc, $desc]);
            } else {
                secupay_log::log(self::$secupay_log, "secupay_events, createPayment, desc missing");
            }
        } catch (Exception $e) {
            secupay_log::log(self::$secupay_log, "secupay_events, Exception:", $e->getMessage());
            secupay_log::log(self::$secupay_log, "secupay_events, Exception Trace:", $e->getTraceAsString());
        }
    }

    private static function checkTableStructure() {
        try {
            $oDB = oxDb::getDb(true);

            foreach (self::$secupay_table_names as $table_name) {
                $table_exists = $oDB->getOne("SHOW TABLES LIKE '".$table_name."'");

                if (!isset($table_exists) || !$table_exists) {
                    self::createTableStructure($table_name);
                } else {
                    switch ($table_name) {
                        case 'oxsecupay':
                            //check columns of table oxsexupay
                            $sSql_columns = 'SHOW COLUMNS FROM oxsecupay WHERE Field IN ('.self::$secupay_oxsecupay_coulmn_names.');';
                            $columns_match = count($oDB->getAll($sSql_columns)) == 21;
                            break;
                        case 'oxsecupay_iframe_url':
                            //check columns of table oxsecupay_iframe_url
                            $sSql_columns = 'SHOW COLUMNS FROM oxsecupay_iframe_url WHERE Field IN ('.self::$secupay_oxsecupay_iframe_url_coulmn_names.');';
                            $columns_match = count($oDB->getAll($sSql_columns)) == 2;
                            break;
                        case 'oxsecupay_status':
                            //check columns of table oxsecupay_status
                            $sSql_columns = 'SHOW COLUMNS FROM oxsecupay_status WHERE Field IN ('.self::$secupay_oxsecupay_status_coulmn_names.');';
                            $columns_match = count($oDB->getAll($sSql_columns)) == 5;
                            break;
                        default :
                            secupay_log::log(self::$secupay_log, "secupay_events, checkTableStructure, structure unkown for table '" . $table_name . "'");
                    }

                    if (isset($columns_match) && !$columns_match) {
                        secupay_log::log(self::$secupay_log, "secupay_events, checkTableStructure, columns do not match for " . $table_name);
                        $backup_table_name = $table_name .'_backup_'. uniqid();
                        secupay_log::log(self::$secupay_log, "secupay_events, checkTableStructure, rename '" . $table_name . "' to '" . $backup_table_name . "'");
                        $sSql_rename = "RENAME TABLE " . $table_name . " TO " . $backup_table_name . ";";
                        $oDB->execute($sSql_rename);
                        secupay_log::log(self::$secupay_log, "secupay_events, checkTableStructure, create '" . $table_name . "'");
                        self::createTableStructure($table_name);
                    }
                }
            }
        } catch (Exception $e) {
            secupay_log::log(self::$secupay_log, "secupay_events, Exception:", $e->getMessage());
            secupay_log::log(self::$secupay_log, "secupay_events, Exception Trace:", $e->getTraceAsString());
        }
    }

    private static function createTableStructure($table_name = 'oxsecupay') {
        try {
            $oDB = oxDb::getDb(true);
            switch ($table_name) {
                case 'oxsecupay':
                    //table oxsecupay
                    $sSql = "CREATE TABLE `oxsecupay` (
                    `oxsecupay_id` int(10) unsigned NOT NULL auto_increment,
                    `req_data` text collate latin1_general_ci,
                    `ret_data` text collate latin1_general_ci,
                    `payment_method` varchar(255) collate latin1_general_ci default NULL,
                    `transaction_id` int(10) unsigned default NULL,
                    `oxordernr` int(11) default NULL,
                    `oxorder_id` char(32) default NULL,
                    `oxsecupay_status_id` int(10) unsigned default NULL,
                    `rank` int(10) default NULL,
                    `amount` varchar(255) collate latin1_general_ci default NULL,
                    `action` text collate latin1_general_ci,
                    `iframe_url_id` int(10) unsigned NOT NULL,
                    `updated` int(2) unsigned default '0',
                    `v_send` int(2) unsigned default NULL,
                    `v_status` int(10) unsigned default NULL,
                    `searchcode` varchar(255) collate latin1_general_ci default NULL,
                    `tracking_code` varchar(255) collate latin1_general_ci default NULL,
                    `carrier_code` varchar(255) collate latin1_general_ci default NULL,
                    `hash` varchar(255) default NULL,
                    `created` datetime NOT NULL,
                    `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
                    PRIMARY KEY  (`oxsecupay_id`)
                  ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;";
                    $oDB->execute($sSql);
                    break;
                case 'oxsecupay_iframe_url':
                    //table oxsecupay_iframe_url
                    $sSql = "CREATE TABLE `oxsecupay_iframe_url` (
                    `iframe_url_id` int(10) unsigned NOT NULL auto_increment,
                    `iframe_url` text collate latin1_general_ci,
                    PRIMARY KEY  (`iframe_url_id`)
                  ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;";
                    $oDB->execute($sSql);
                    break;
                case 'oxsecupay_status':
                    //table oxsecupay_status
                    $sSql = "CREATE TABLE `oxsecupay_status` (
                    `oxsecupay_status_id` int(10) unsigned NOT NULL auto_increment,
                    `oxsecupay_id` int(10) unsigned NOT NULL,
                    `msg` varchar(255) collate latin1_general_ci default NULL,
                    `status` varchar(255) collate latin1_general_ci default NULL,
                    `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,                    
                    PRIMARY KEY  (`oxsecupay_status_id`)
                  ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;";
                    $oDB->execute($sSql);
                    break;
                default :
                    secupay_log::log(self::$secupay_log, "secupay_events, createTableStructure, unknown tablename: " . $table_name);
            }
        } catch (Exception $e) {
            secupay_log::log(self::$secupay_log, "secupay_events, Exception:", $e->getMessage());
            secupay_log::log(self::$secupay_log, "secupay_events, Exception Trace:", $e->getTraceAsString());
        }
    }

    private static function requestAvailablePaymenttypes() {
        try {
            //$apikey = oxConfig::getConfig()->getConfigParam('secupay_api_key');
            $apikey = oxRegistry::getConfig()->getConfigParam('secupay_api_key');

            if (isset($apikey)) {
                $daten = [
                    'apikey' => $apikey,
                    'apiversion' => secupay_api::get_api_version()
                ];
                $sp_api = new secupay_api($daten, 'gettypes');
                $api_return = $sp_api->request();

                secupay_log::log(self::$secupay_log, "secupayPayment, requestAvailablePaymenttypes api response data:", $api_return);
                secupay_log::log(self::$secupay_log, "secupayPayment, requestAvailablePaymenttypes api check response: ", $api_return->check_response());

                if ($api_return->check_response()) {
                    self::$secupay_payment_types_active = $api_return->data;
                    #secupay_log::log($secupay_log, "secupayPayment, getSecupayPaymentTypes, secupay_payment_types_activ:",$this->secupay_payment_types_activ);
                } else {
                    secupay_log::log(self::$secupay_log, "secupayPayment Response Error");
                }
            }
        } catch (Exception $e) {
            secupay_log::log(self::$secupay_log, "secupay_events, Exception:", $e->getMessage());
            secupay_log::log(self::$secupay_log, "secupay_events, Exception Trace:", $e->getTraceAsString());
        }
    }
}