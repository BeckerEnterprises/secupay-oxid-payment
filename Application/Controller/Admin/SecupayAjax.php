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
 *
 * OXID 6 Migration
 * (c) Becker Enterprises 2022
 * Dirk Becker, Germany
 * https://becker.enterprises
 */

namespace Secupay\Payment\Application\Controller\Admin
{
	use \Secupay\Payment\Core\SecupayApi;
	use \Secupay\Payment\Core\Table;
	use \Secupay\Payment\Core\Logger;

	use \OxidEsales\Eshop\Core\Registry;
	use \OxidEsales\Eshop\Application\Controller\Admin\AdminController;

	class SecupayAjax extends AdminController
	{
		public function init()
		{
			parent::init();
			$this->_sThisTemplate = 'secupay/admin/ajaxrequest.tpl';
		}

		public function render()
		{
			parent::render();
			return $this->_sThisTemplate;
		}

		public function refund()
		{
			$oLang = Registry::getLang();

			$sApiKey = array_key_exists('apikey', $_POST) ? $_POST['apikey'] : null;
			$fAmmount = array_key_exists('amount', $_POST) ? floatval($_POST['amount']) * 100.0 : null;
			$sHash = array_key_exists('hash', $_POST) ? $_POST['hash'] : null;

			$aOutput = ['status' => 'error'];
			if($sApiKey && $fAmmount && $sHash)
			{
				$oApi = new SecupayApi(['apikey' => $sApiKey, 'amount' => $fAmmount, 'hash' => $sHash], 'refund');
				if(($oResponse = $oApi->request()) && $oResponse->checkResponse() && ($oData = $oResponse->getData()) && property_exists($oData, 'status') && ($sStatus = $oData->status))
				{
					Table::createStatusEntry($sHash, 'refund: '.$sStatus, '');
					$aOutput['status'] = $oData->status;
					$aOutput['message'] = $oLang->translateString('SECUPAY_AJAX_REFUND').$oData->status;
					$aOutput['response'] = $oData;
				}
				else
					$aOutput['message'] = $oLang->translateString('SECUPAY_AJAX_ERROR_REQUEST_FAILED');
			}
			else
				$aOutput['message'] = $oLang->translateString('SECUPAY_AJAX_ERROR_PARAMS_MISSING');

			$this->_aViewData['data'] = json_encode($aOutput, JSON_UNESCAPED_UNICODE);
		}

		public function status()
		{
			$oLang = Registry::getLang();

			$sApiKey = array_key_exists('apikey', $_POST) ? $_POST['apikey'] : null;
			$sHash = array_key_exists('hash', $_POST) ? $_POST['hash'] : null;

			$aOutput = ['status' => 'error'];
			if($sApiKey && $sHash)
			{
				$oApi = new SecupayApi(['apikey' => $sApiKey, 'hash' => $sHash], 'status');
				if(($oResponse = $oApi->request()) && $oResponse->checkResponse() && ($oData = $oResponse->getData()) && property_exists($oData, 'status') && ($sStatus = $oData->status))
				{
					if(property_exists($oData, 'trans_id') && ($sTransactionId = $oData->trans_id))
						Table::setTransactionId($sHash, $oData->trans_id);

					$sStatusDescription = property_exists($oData, 'status_description') ? $oData->status_description : null;
					$sPaymentStatus = property_exists($oData, 'payment_status') && strlen($oData->payment_status) > 2 ? $oData->payment_status : null;

					if($sStatusDescription || $sPaymentStatus)
						Table::createStatusEntry($sHash, 'Status: '.$sPaymentStatus, $sStatusDescription);

					$aOutput['status'] = $oData->status;
					$aOutput['message'] = $oLang->translateString('SECUPAY_AJAX_STATUS').$oData->status;
					$aOutput['response'] = $oData;
				}
				else
					$aOutput['message'] = $oLang->translateString('SECUPAY_AJAX_ERROR_REQUEST_FAILED');
			}
			else
				$aOutput['message'] = $oLang->translateString('SECUPAY_AJAX_ERROR_PARAMS_MISSING');

			$this->_aViewData['data'] = json_encode($aOutput, JSON_UNESCAPED_UNICODE);
		}
	}
}