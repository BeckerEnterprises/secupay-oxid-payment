<?php declare(strict_types=1);
/**
  * secupay Payment Module
 * @author    secupay AG
 * @copyright 2019, secupay AG
 * @license   LICENSE.txt
 * @category  Payment
 *
 * Description:
 *  Oxid Plugin for integration of secupay AG payment services
 *
 * OXID 6 Migration
 * (c) Becker Enterprises 2022
 * Dirk Becker, Germany
 * https://becker.enterprises
 */

namespace Secupay\Payment\Core
{
	use \OxidEsales\Eshop\Core\ShopVersion;

	class SecupayApi
	{
		const API_VERSION = '2.3';
		const CLIENT_VERSION = '1.2.0_OX6';

		const SECUPAY_HOST = 'api.secupay.ag';
		const SECUPAY_URL  = 'https://api.secupay.ag/payment/';
		const SECUPAY_PATH = '/payment/';
		const SECUPAY_PORT = 443;

		const DEFAULT_LANGUAGE = 'de_de';
		const DEFAULT_REQUEST_FUNCTION = 'init';
		const REQUEST_FORMAT_JSON = 'application/json';
		const REQUEST_FORMAT_XML = 'text/xml';

		private $oParams = null;
		private $sReqFormat = null;
		private $sReqFunction = null;
		private $blLoggingEnabled = null;
		private $sLanguage = null;
		private $sSentData = null;
		private $sReceivedData = null;

		public function __construct($oParams, string $sReqFunction = 'init', string $sReqFormat = self::REQUEST_FORMAT_JSON, bool $blLoggingEnabled = false, string $sLanguage = self::DEFAULT_LANGUAGE)
		{
			$this->setParams($oParams);
			$this->setRequestFunction($sReqFunction);
			$this->setRequestFormat($sReqFormat);
			$this->setLoggingEnabled($blLoggingEnabled);
			$this->setLanguage($sLanguage);
		}

		public static function getApiVersion()
		{
			return self::API_VERSION;
		}

		public static function getClientVersion()
		{
			return self::CLIENT_VERSION;
		}

		public function request()
		{
			return function_exists('curl_init') ? $this->requestByCurl() : $this->requestBySocketStream();
		}

		private function requestByCurl()
		{
			$oParams = $this->getParams();
			$aData = json_encode(['data' => ($oParams && is_string($oParams) ? Codec::ensureUTF8($oParams) : $oParams)]);
			Logger::log($this->isLoggingEnabled(), 'CURL DATA ', $aData);

			$aHeader = [
				'Accept-Language: '.$this->getLanguage(self::DEFAULT_LANGUAGE),
				'Accept: '.$this->getRequestFormat(self::REQUEST_FORMAT_JSON),
				'Content-Type: application/json',
				'User-Agent: OXID '.ShopVersion::getVersion().' client '.self::CLIENT_VERSION,
				'Content-Length: '.strlen($aData),
			];

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, self::SECUPAY_URL.$this->getRequestFunction(self::DEFAULT_REQUEST_FUNCTION));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $aData);

			Logger::log($this->isLoggingEnabled(), 'CURL request for '.self::SECUPAY_URL.$this->getRequestFunction(self::DEFAULT_REQUEST_FUNCTION).' in format : '.$this->getRequestFormat(self::REQUEST_FORMAT_JSON).' language: '.$this->getLanguage(self::DEFAULT_LANGUAGE));
			Logger::log($this->isLoggingEnabled(), $aData);

			$sResponse = curl_exec($ch);
			Logger::log($this->isLoggingEnabled(), 'Response: '.$sResponse);

			$this->setSentData($aData);
			$this->setReceivedData($sResponse);

			curl_close($ch);
			return $this->parseAnswer($sResponse);
		}

		private function requestBySocketStream()
		{
			$oParams = $this->getParams();
			$sData = json_encode(['data' => ($oParams && is_string($oParams) ? Codec::ensureUTF8($oParams) : $oParams)]);

			$fp = fsockopen('ssl://'.self::SECUPAY_HOST, self::SECUPAY_PORT, $errstr, $errno);
			if(!$fp)
			{
				Logger::log(true, 'SOCKETSTREAM Error: Can not connect to secupay api!');
				Logger::log(true, 'Server not reachable: '.self::SECUPAY_HOST.':'.self::SECUPAY_PORT);
				return false;
			}
			else
			{
				$sRequest  = "POST ".self::SECUPAY_PATH.$this->getRequestFunction(self::DEFAULT_REQUEST_FUNCTION)." HTTP/1.1\r\n";
				$sRequest .= "Host: ".self::SECUPAY_HOST."\r\n";
				$sRequest .= "Content-type: application/json; Charset:UTF8\r\n";
				$sRequest .= "Accept: ".$this->getRequestFormat(self::REQUEST_FORMAT_JSON)."\r\n";
				$sRequest .= "User-Agent: OXID ".ShopVersion::getVersion()." client 1.2 BE\r\n";
				$sRequest .= "Accept-Language: ".$this->getLanguage(self::DEFAULT_LANGUAGE)."\r\n";
				$sRequest .= "Content-Length: ".strlen($sData)."\r\n";
				$sRequest .= "Connection: close\r\n\r\n";
				$sRequest .= $sData;

				Logger::log($this->isLoggingEnabled(), 'SOCKETSTREAM request for '.self::SECUPAY_URL.$this->getRequestFunction(self::DEFAULT_REQUEST_FUNCTION).' in format : '.$this->getRequestFormat(self::REQUEST_FORMAT_JSON).' language: '.$this->getLanguage(self::DEFAULT_LANGUAGE));
				Logger::log($this->isLoggingEnabled(), $sData);

				fputs($fp, $sRequest);
			}

			$sReceivedData = '';
			$sBuffer = '';
			while(!feof($fp))
			{
				$sBuffer = fgets($fp, 128);
				$sReceivedData .= $sBuffer;
			}
			fclose($fp);

			$pos  = strpos($sReceivedData, "\r\n\r\n");
			$sReceivedData = substr($sReceivedData, $pos + 4);

			Logger::log($this->isLoggingEnabled(), 'Response: '.$sReceivedData);

			$this->setSentData($sData);
			$this->setReceivedData($sReceivedData);

			return $this->parseAnswer($sReceivedData);
		}

		private function parseAnswer($sReceivedData)
		{
			$sReqFormat = $this->getRequestFormat(self::REQUEST_FORMAT_JSON);
			switch($sReqFormat)
			{
				case self::REQUEST_FORMAT_JSON:
					if($answer = json_decode($sReceivedData))
						return new SecupayApiResponse($answer);

				case self::REQUEST_FORMAT_XML:
					if($answer = simplexml_load_string($sReceivedData))
						return new SecupayApiResponse($answer);

				default:
					return $sReceivedData;
			}
		}

		public function getParams()
		{
			return $this->oParams;
		}
		public function setParams($oParams)
		{
			$this->oParams = $oParams;
		}

		public function getRequestFunction($default = null) : ?string
		{
			if($this->sReqFunction === null && $default !== null)
				return $default;

			return $this->sReqFunction;
		}
		public function setRequestFunction(string $sReqFunction = null)
		{
			$this->sReqFunction = $sReqFunction;
		}

		public function getRequestFormat($default = null) : ?string
		{
			if($this->sReqFormat === null && $default !== null)
				return $default;

			return $this->sReqFormat;
		}
		public function setRequestFormat(string $sReqFormat = null)
		{
			$this->sReqFormat = $sReqFormat;
		}

		public function isLoggingEnabled() : bool
		{
			return $this->blLoggingEnabled;
		}
		public function setLoggingEnabled(bool $blLoggingEnabled)
		{
			$this->blLoggingEnabled = $blLoggingEnabled;
		}

		public function getLanguage($default = null) : ?string
		{
			if($this->sLanguage === null && $default !== null)
				return $default;

			return $this->sLanguage;
		}
		public function setLanguage(string $sLanguage = null)
		{
			if(($sLanguage === null) || preg_match('/^[a-z]{2}_[a-z]{2}$/i', $sLanguage))
			{
				$this->sLanguage = $sLanguage;
				return true;
			}
			return false;
		}

		public function getSentData() : ?string
		{
			return $this->sSentData;
		}
		protected function setSentData(string $sSentData = null)
		{
			$this->sSentData = $sSentData;
		}

		public function getReceivedData() : ?string
		{
			return $this->sReceivedData;
		}
		protected function setReceivedData(string $sReceivedData = null)
		{
			$this->sReceivedData = $sReceivedData;
		}
	}
}