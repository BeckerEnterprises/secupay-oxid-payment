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

/*
define('SECUPAY_HOST', 'api-dist.secupay-ag.de');
define('SECUPAY_URL', 'https://'.SECUPAY_HOST.'/payment/');
define('SECUPAY_PATH', '/payment/');
define('SECUPAY_PORT', 443);
*/

use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;

/**
 *
 */
define('SECUPAY_HOST', 'api.secupay.ag');
/**
 *
 */
define('SECUPAY_URL', 'https://' . SECUPAY_HOST . '/payment/');
/**
 *
 */
define('SECUPAY_PATH', '/payment/');
/**
 *
 */
define('SECUPAY_PORT', 443);

if (!class_exists("secupay_log")) {
    /**
     * Class secupay_log
     */
    class secupay_log
    {

        /**
         * @param $log
         */
        static function log($log)
        {
            //static function log() {

            if (!$log) {
                return;
            }

            $date    = date("r");
            $logfile = getShopBasePath() . "log/splog.log";
            $x       = 0;
            foreach (func_get_args() as $val) {
                $x++;
                if ($x == 1) {
                    continue;
                }
                if (is_string($val) || is_numeric($val)) {
                    //file_put_contents(self::$logfile, "[$date] $val\n", FILE_APPEND);
                    file_put_contents($logfile, "[$date] $val\n", FILE_APPEND);
                } else {
                    //file_put_contents(self::$logfile, "[$date] ".print_r($val,true)."\n", FILE_APPEND);
                    file_put_contents($logfile, "[$date] " . print_r($val, true) . "\n", FILE_APPEND);
                }
            }
        }
    }
}

if (!function_exists('seems_utf8')) {
    /**
     * @param $Str
     *
     * @return bool
     */
    function seems_utf8($Str)
    {
        for ($i = 0; $i < strlen($Str); $i++) {
            if (ord($Str[$i]) < 0x80) continue; # 0bbbbbbb
            elseif ((ord($Str[$i]) & 0xE0) == 0xC0) $n = 1; # 110bbbbb
            elseif ((ord($Str[$i]) & 0xF0) == 0xE0) $n = 2; # 1110bbbb
            elseif ((ord($Str[$i]) & 0xF8) == 0xF0) $n = 3; # 11110bbb
            elseif ((ord($Str[$i]) & 0xFC) == 0xF8) $n = 4; # 111110bb
            elseif ((ord($Str[$i]) & 0xFE) == 0xFC) $n = 5; # 1111110b
            else return false; // Does not match any model

            for ($j = 0; $j < $n; $j++) {
                // n bytes matching 10bbbbbb follow ?
                if ((++$i == strlen($Str)) || ((ord($Str[$i]) & 0xC0) != 0x80)) {
                    return false;
                }
            }
        }
        return true;
    }
}

if (!function_exists('utf8_ensure')) {
    /**
     * @param $data
     *
     * @return array|string
     */
    function utf8_ensure($data)
    {
        if (is_string($data)) {
            return seems_utf8($data) ? $data : utf8_encode($data);
        } elseif (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = utf8_ensure($value);
            }
            unset($value);
            unset($key);
        } elseif (is_object($data)) {
            foreach ($data as $key => $value) {
                $data->$key = utf8_ensure($value);
            }
            unset($value);
            unset($key);
        }
        return $data;
    }
}

if (!class_exists("secupay_api")) {
    /**
     * Class secupay_api
     */
    class secupay_api
    {

        /**
         * @var
         */
        /**
         * @var string
         */
        /**
         * @var array|string
         */
        /**
         * @var array|string
         */
        /**
         * @var array|string
         */
        /**
         * @var array|string
         */
        /**
         * @var array|bool|string
         */
        /**
         * @var array|bool|string
         */
        var $schnittstelle, $req_format, $data, $req_function, $sent_req, $error, $sp_log, $language;

        /**
         * secupay_api constructor.
         *
         * @param        $params
         * @param string $req_function
         * @param string $format
         * @param bool   $sp_log
         * @param string $language
         */
        public function __construct($params, $req_function = 'init', $format = 'application/json', $sp_log = false, $language = 'de_de')
        {
            $this->req_function = $req_function;
            $this->req_format   = $format;
            $this->sp_log       = $sp_log;
            $this->language     = $language;
            $this->data         = array(
                'data' => $params
            );
        }

        /**
         * @return bool|null|secupay_api_response
         */
        function request()
        {
            $rc = null;
            if (function_exists("curl_init")) {
                $rc = $this->request_by_curl();
            } else {
                $rc = $this->request_by_socketstream();
            }

            return $rc;
        }

        /**
         * @return secupay_api_response
         */
        function request_by_curl()
        {
            $_data = json_encode(utf8_ensure($this->data));

            secupay_log::log($this->sp_log, "CURL DATA ", $_data);

            $http_header = array(
                'Accept-Language: ' . $this->language,
                'Accept: ' . $this->req_format,
                'Content-Type: application/json',
                'User-Agent: OXID 4.7 client 1.2',
                'Content-Length: ' . strlen($_data)
            );

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, SECUPAY_URL . $this->req_function);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $_data);

            secupay_log::log(
                $this->sp_log,
                'CURL request for ' . SECUPAY_URL . $this->req_function . ' in format : ' . $this->req_format
                . ' language: ' . $this->language
            );
            secupay_log::log($this->sp_log, $_data);

            $rcvd = curl_exec($ch);
            secupay_log::log($this->sp_log, 'Response: ' . $rcvd);

            $this->sent_data  = $_data;
            $this->recvd_data = $rcvd;

            curl_close($ch);
            return $this->parse_answer($this->recvd_data);
        }

        /**
         * @param $ret
         *
         * @return secupay_api_response
         */
        function parse_answer($ret)
        {
            switch (strtolower($this->req_format)) {
                case "application/json":
                    $answer = json_decode($ret);
                    break;
                case "text/xml":
                    $answer = simplexml_load_string($ret);
                    break;
            }
            #return $answer;
            $api_response = new secupay_api_response($answer);
            return $api_response;
        }

        /**
         * @return bool|secupay_api_response
         */
        function request_by_socketstream()
        {
            $_data = json_encode(utf8_ensure($this->data));

            $rcvd       = "";
            $rcv_buffer = "";
            $fp         = fsockopen('ssl://' . SECUPAY_HOST, SECUPAY_PORT, $errstr, $errno);

            if (!$fp) {
                $this->error = "can't connect to secupay api";
                return false;
            } else {
                $req = "POST " . SECUPAY_PATH . $this->req_function . " HTTP/1.1\r\n";
                $req .= "Host: " . SECUPAY_HOST . "\r\n";
                $req .= "Content-type: application/json; Charset:UTF8\r\n";
                $req .= "Accept: " . $this->req_format . "\r\n";
                $req .= "User-Agent: OXID 4.7 client 1.2\r\n";
                $req .= "Accept-Language: " . $this->language . "\r\n";
                $req .= "Content-Length: " . strlen($_data) . "\r\n";
                $req .= "Connection: close\r\n\r\n";
                $req .= $_data;
                /*
                $req = "POST /api/{$this->req_function}.{$this->req_format} HTTP/1.1\r\n";
                $req.="Host: connect.secupay.ag\r\n";
                $req.="Content-type: application/x-www-form-urlencoded; Charset:UTF8\r\n";
                $req.="Content-length: " . strlen($_data) . "\r\n";
                $req.="Connection: close\r\n\r\n";
                $req.=$_data;
                */

                secupay_log::log(
                    $this->sp_log,
                    'SOCKETSTREAM request for ' . SECUPAY_URL . $this->req_function . ' in format : '
                    . $this->req_format . ' language: ' . $this->language
                );
                secupay_log::log($this->sp_log, $_data);

                fputs($fp, $req);
            }

            while (!feof($fp)) {
                $rcv_buffer = fgets($fp, 128);
                $rcvd       .= $rcv_buffer;
            }
            fclose($fp);

            $pos  = strpos($rcvd, "\r\n\r\n");
            $rcvd = substr($rcvd, $pos + 4);

            secupay_log::log($this->sp_log, 'Response: ' . $rcvd);

            $this->sent_data  = $_data;
            $this->recvd_data = $rcvd;

            return $this->parse_answer($this->recvd_data);
        }

        /**
         * @return string
         */
        static function get_api_version()
        {
            return '2.3';
        }
    }
}

if (!class_exists("secupay_api_response")) {
    /**
     * Class secupay_api_response
     */
    class secupay_api_response
    {

        /**
         * @var
         */
        /**
         * @var
         */
        /**
         * @var
         */
        /**
         * @var
         */
        var $status, $data, $errors, $raw_data;

        /**
         * secupay_api_response constructor.
         *
         * @param $answer
         */
        public function __construct($answer)
        {

            $this->status   = $answer->status;
            $this->errors   = $answer->errors;
            $this->data     = $answer->data;
            $this->raw_data = $answer;
        }

        /**
         * @param bool $log_error
         *
         * @return bool
         */
        function check_response($log_error = false)
        {

            if (strtolower($this->status) != 'ok') {
                if ($log_error) secupay_log::log("secupay_api_response status: ", $this->status);
                return false;
            };

            if (count($this->errors) > 0) {
                if ($log_error) secupay_log::log("secupay_api_response error: ", $this->errors);
                return false;
            }

            if (count($this->data) == 0) {
                if ($log_error) secupay_log::log("secupay_api_response error: no data in response");
                return false;
            }

            return true;
        }

        /**
         * @return bool
         */
        function get_hash()
        {
            if (isset($this->data->hash)) {
                return $this->data->hash;
            } else {
                return false;
            }
        }

        /**
         * @return bool
         */
        function get_iframe_url()
        {
            if (isset($this->data->iframe_url)) {
                return $this->data->iframe_url;
            } else {
                return false;
            }
        }

        /**
         * @param bool $log_error
         *
         * @return bool
         */
        function get_status($log_error = false)
        {
            if ($log_error) secupay_log::log("secupay_api_response get_status: " . $this->status);
            if (isset($this->status)) {
                return $this->status;
            } else {
                return false;
            }
        }

        /**
         * @param bool $log_error
         *
         * @return bool|string
         */
        function get_error_message($log_error = false)
        {
            $message = '';
            if (isset($this->errors)) {
                foreach ($this->errors as $error) {
                    $message = $message . '(' . $error->code . ') ' . $error->message;
                    if (isset($error->field)) {
                        $message = $message . '<br>' . $error->field;
                    }
                    $message = $message . '<br>';
                }
                if ($log_error) secupay_log::log("secupay_api_response get_error_message: " . $message);
                return $message;
            } else {
                return false;
            }
        }

        /**
         * @param bool $log_error
         *
         * @return bool|string
         */
        function get_error_message_user($log_error = false)
        {
            $message = '';
            if (isset($this->errors)) {
                foreach ($this->errors as $error) {
                    $message = $message . '(' . $error->code . ')';
                    if ($this->status == 'failed') {
                        $message = $message . ' ' . $error->message;
                    }
                }
                if ($log_error) secupay_log::log("secupay_api_response get_error_message_user: " . $message);
                return $message;
            } else {
                return false;
            }
        }
    }
}

if (!class_exists("secupay_table")) {
    /**
     * Class secupay_table
     */
    class secupay_table
    {

        /**
         * @param      $hash
         * @param      $msg
         * @param      $status
         * @param bool $log_error
         *
         * @return bool|int
         * @throws DatabaseConnectionException
         * @throws DatabaseErrorException
         */
        static function createStatusEntry($hash, $msg, $status, $log_error = false)
        {
            $oDB = oxDb::getDb(true);

            $oxsecupay_id = intval($oDB->getOne("SELECT oxsecupay_id FROM oxsecupay WHERE hash = ?", [$hash]));

            if (isset($oxsecupay_id) && is_numeric($oxsecupay_id)) {
                $sSql_insert = "INSERT INTO oxsecupay_status (oxsecupay_id, status, msg) VALUES(?, ?, ?)";
                secupay_log::log($log_error, 'createStatusEntry insert: ', $sSql_insert);
                $oDB->execute($sSql_insert, [$oxsecupay_id, $status, $msg]);

                $status_id = intval($oDB->getOne("SELECT LAST_INSERT_ID()"));

                if (isset($status_id) && $status_id > 0) {
                    $sSql_update = "UPDATE oxsecupay SET oxsecupay_status_id = ? WHERE hash = ?";
                    secupay_log::log($log_error, 'createStatusEntry update: ', $sSql_update);
                    $oDB->execute($sSql_update, [$status_id, $hash]);
                }
                return $status_id;
            } else {
                return false;
            }
        }

        /**
         * @param      $req_data
         * @param      $ret_data
         * @param      $hash
         * @param      $payment_method
         * @param      $oxorder_id
         * @param      $amount
         * @param      $iframe_url
         * @param bool $log_error
         *
         * @throws DatabaseConnectionException
         * @throws DatabaseErrorException
         */
        static function createTransactionEntry($req_data, $ret_data, $hash, $payment_method, $oxorder_id, $amount, $iframe_url, $log_error = false)
        {
            $oDB = oxDb::getDb(true);

            $iframe_url_id = intval(self::getIframeUrlId($hash, $iframe_url, $log_error));

            $sSQL = "INSERT INTO oxsecupay (req_data, ret_data, hash, payment_method, oxorder_id, amount, rank, created, iframe_url_id)"
                . " VALUES(?, ?, ?, ?, ?, ?, 99, now(), ?)";

            $oDB->execute($sSQL, [$req_data, $ret_data, $hash, $payment_method, $oxorder_id, $amount, $iframe_url_id]);
        }

        /**
         * @param      $hash
         * @param      $iframe_url
         * @param bool $log_error
         *
         * @return false|int|string
         * @throws DatabaseConnectionException
         * @throws DatabaseErrorException
         */
        static function getIframeUrlId($hash, $iframe_url, $log_error = false)
        {
            $url = str_replace($hash, '', $iframe_url);

            $oDB = oxDb::getDb(true);

            $sSQL_select   = "SELECT iframe_url_id FROM oxsecupay_iframe_url WHERE iframe_url = ? LIMIT 1";
            $iframe_url_id = $oDB->getOne($sSQL_select, [$url]);

            if (!isset($iframe_url_id) || !is_numeric($iframe_url_id)) {
                $sSQL_insert = "INSERT INTO oxsecupay_iframe_url (iframe_url) VALUES (?)";
                $oDB->execute($sSQL_insert, [$url]);
                $sSQL_last_inserted_id = "SELECT LAST_INSERT_ID()";
                $iframe_url_id         = $oDB->getOne($sSQL_last_inserted_id);
            }

            if (isset($iframe_url_id) && is_numeric($iframe_url_id)) {
                return $iframe_url_id;
            } else {
                secupay_log::log(
                    $log_error,
                    'secupay_api, getIframeUrlId queries',
                    $sSQL_select,
                    $sSQL_insert,
                    $sSQL_last_inserted_id
                );
                return 0;
            }
        }

        /**
         * @param      $hash
         * @param      $transaction_id
         * @param bool $log_error
         *
         * @throws DatabaseConnectionException
         * @throws DatabaseErrorException
         */
        static function setTransactionId($hash, $transaction_id, $log_error = false)
        {
            if (!empty($hash) & !empty($transaction_id)) {
                $oDB = oxDb::getDb(true);

                $sqlQuery = "UPDATE oxsecupay SET transaction_id = ? WHERE ISNULL(transaction_id) AND hash = ?";
                secupay_log::log($log_error, 'secupay_api, setTransactionId queries', $sqlQuery);
                $oDB->execute($sqlQuery, [$transaction_id, $hash]);
            } else {
                secupay_log::log($log_error, 'secupay_api, hash or transaction_id empty');
            }
        }

        /**
         * @param      $hash
         * @param      $rank
         * @param bool $log_error
         *
         * @throws DatabaseConnectionException
         * @throws DatabaseErrorException
         */
        static function setTransactionRank($hash, $rank, $log_error = false)
        {
            if (!empty($hash) & !empty($rank)) {
                $oDB = oxDb::getDb(true);

                $sqlQuery = "UPDATE oxsecupay SET rank = ? WHERE hash = ?";
                secupay_log::log($log_error, 'secupay_api, setTransactionRank queries', $sqlQuery);
                $oDB->execute($sqlQuery, [$rank, $hash]);
            } else {
                secupay_log::log($log_error, 'secupay_api, hash or $rank empty');
            }
        }
    }
}
