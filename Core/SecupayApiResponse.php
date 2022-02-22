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
	class SecupayApiResponse
	{
		private $status = null;
		private $data = null;
		private $errors = null;
		private $raw_data = null;

		public function __construct($oReceivedData)
		{
			if($oReceivedData)
			{
				if(property_exists($oReceivedData, 'status'))
					$this->setStatus(trim(strval($oReceivedData->status)));

				if(property_exists($oReceivedData, 'errors') && is_array($oReceivedData->errors))
					$this->setErrors($oReceivedData->errors);

				if(property_exists($oReceivedData, 'data'))
					$this->setData($oReceivedData->data);

				$this->setRawData($oReceivedData);
			}
		}

		public function checkResponse(bool $blLoggingEnabled = false) : bool
		{
			if($this->getStatus() != 'ok')
			{
				Logger::log($blLoggingEnabled, 'secupay_api_response status: ', $this->getStatus())
				return false;
			}

			if(count($this->getErrors()) > 0)
			{
				Logger::log($blLoggingEnabled, 'secupay_api_response error: ', $this->getErrors());
				return false;
			}

			if(!$this->getData() || (is_array($this->getData()) && (count($this->getData()) == 0)))
			{
				Logger::log($blLoggingEnabled, 'secupay_api_response error: no data in response');
				return false;
			}

			return true;
		}

		public function getStatus(bool $blLoggingEnabled = false) : ?string
		{
			Logger::log($blLoggingEnabled, 'secupay_api_response get_status: '.$this->status);
			return $this->status;
		}
		protected function setStatus(string $sStatus = null)
		{
			$this->status = $sStatus;
		}

		public function getErrors() : ?array
		{
			return $this->errors;
		}
		protected function setErrors(array $aErrors = null)
		{
			$this->errors = $aErrors;
		}
		function getErrorMessage(bool $blLoggingEnabled = false) : ?string
		{
			if($this->errors && (count($this->errors) > 0))
			{
				$messages = [];
				foreach($this->errors as $error)
				{
					$messages[] = (property_exists($error, 'code') && $error->code ? '('.$error->code.') ' : '').
								  (property_exists($error, 'message') && $error->message ? $error->message : 'unknown error').
								  (property_exists($error, 'field') && $error->field ? '<br>'.$error->field : '');
				}

				if($messages = implode('<br>', $messages))
				{
					Logger::log($blLoggingEnabled, 'secupay_api_response get_error_message: ', $messages);
					return $messages;
				}
			}
			return null;
		}
		function getErrorMessageUser(bool $blLoggingEnabled = false) : ?array
		{
			if($this->errors && (count($this->errors) > 0))
			{
				$messages = [];
				foreach($this->errors as $error)
				{
					if(preg_match('/^failed$/i', $this->getStatus()))
					{
						$messages[] = (property_exists($error, 'code') && $error->code ? '('.$error->code.') ' : '').
									  (property_exists($error, 'message') && $error->message ? $error->message : 'unknown error').
									  (property_exists($error, 'field') && $error->field ? '<br>'.$error->field : '');
					}
				}

				if($messages = implode('<br>', $messages))
				{
					Logger::log($blLoggingEnabled, 'secupay_api_response get_error_message_user: ', $messages);
					return $messages;
				}
			}
			return null;
		}

		public function getData()
		{
			return $this->data;
		}
		protected function setData($oData = null)
		{
			$this->data = $oData;
		}
		function getHash() : ?string
		{
			return $this->data && property_exists($this->data, 'hash') ? $this->data->hash : null;
		}
		function getIFrameURL() : ?string
		{
			return $this->data && property_exists($this->data, 'iframe_url') ? $this->data->iframe_url : null;
		}

		public function getRawData() : ?string
		{
			return $this->raw_data;
		}
		protected function setRawData($oRawData = null)
		{
			$this->raw_data = $oRawData;
		}
	}
}