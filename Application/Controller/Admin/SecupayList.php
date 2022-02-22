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
	use \Secupay\Payment\Core\PaymentTypes;

	use \OxidEsales\Eshop\Core\DatabaseProvider;
	use \OxidEsales\Eshop\Application\Controller\Admin\AdminListController;

	class SecupayList extends AdminListController
	{
		private $blLoggingEnabled = false;
		protected $_sThisTemplate = 'secupay/admin/secupay_list.tpl';

		public function init()
		{
			$this->setLoggingEnabled(boolval($this->getConfig()->getConfigParam('blSecupayPaymentDebug')));

			parent::init();

			$sApiKey = $this->getConfig()->getConfigParam('sSecupayPaymentApiKey');

			$oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
			foreach($oDB->getAll('SELECT * FROM oxsecupay WHERE updated = 0 AND `hash` <> \'\';') as $row)
				$this->fetchSecupayStatus($row['hash'], $sApiKey);
		}

		public function render()
		{
			$sReturn = parent::render();

			$oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
			$sApiKey = $this->getConfig()->getConfigParam('sSecupayPaymentApiKey');

			$aTaList = [];
			$sQuery = 'SELECT * FROM oxsecupay s LEFT JOIN oxsecupay_status ss ON ss.oxsecupay_status_id = s.oxsecupay_status_id LEFT JOIN oxsecupay_iframe_url iu ON iu.iframe_url_id = s.iframe_url_id WHERE req_data IS NOT NULL ORDER BY s.`rank`, s.created DESC;';
			foreach($oDB->getAll($sQuery) as $aRow)
			{
				if($aRow['oxsecupay_id'] && $aRow['rank'] && (intval($aRow['rank']) < 99))
				{
					$aMessages = [];
					$sMessageQuery = "SELECT CONCAT_WS(' - ', DATE_FORMAT(`timestamp`, '%d.%m.%Y %T'), msg) AS message FROM oxsecupay_status WHERE oxsecupay_id = ".intval($aRow['oxsecupay_id'])." AND IFNULL(msg, '') <> '' ORDER BY `timestamp` DESC;";
					foreach($oDB->getAll($sMessageQuery) as $aMessageRow)
						$aMessages[] = $aMessageRow['message'];

					$aRow['msg'] = implode('<br>', $aMessages);
				}

				if(strlen($aRow['action']) > 5)
					$aRow['msg'] = '<span style="cursor:pointer;" onclick="'.$aRow['action'].'">'.$aRow['msg'].'</span>';

				$sPartial = $aRow['payment_method'] == 'secupay_creditcard' ? ',true' : '';
				$sCaptureAction = ($aRow['payment_method'] == 'secupay_invoice') && (strlen($aRow['hash']) > 5) ? '<a target="_blank" title="secupay.Rechnungskauf als fällig markieren" href="'.SecupayApi::SECUPAY_URL.$aRow['hash'].'/capture/'.$sApiKey.'" class="sp_capture"><span class="ico"></span></a>' : '';

				if($oPayment = PaymentTypes::getPaymentType($aRow['payment_method']))
					$aRow['payment_method'] = $oPayment->getShortDescription();

				$aRow['amount'] = number_format(floatval($aRow['amount']) / 100.0, 2, ',', '.').' ';

				$aRow['actions'] = '';
				// TODO Funktionen anpassen und wieder aktivieren
				//$aRow['actions']='<a title = "Gutschrift" class="delete" onclick="SP.credit_advice(\''.$aRow['hash'].'\',\''.$aRow['amount'].'\''.$partial.');"></a><a title="Status" onclick="SP.status_request(\''.$aRow['hash'].'\');" class="zoomText"><span class="ico"></span></a>';

				if(strlen($aRow['hash']) > 5)
					$aRow['actions'] .= '<a title="Status abfragen" onclick="SP.status_request(\''.$aRow['hash'].'\');" class="zoomText"><span class="ico"></span></a>';

				$aRow['actions'] .= $sCaptureAction;

				$aTaList[] = $aRow;
			}

			$this->_aViewData['aTaList'] = $aTaList;
			$this->_aViewData['sApiKey'] = $sApiKey;
			$this->_aViewData['sRefundURL'] = $this->getConfig()->getShopHomeURL().'cl=clSecupayAjaxController&fnc=refund';
			$this->_aViewData['sStatusURL'] = $this->getConfig()->getShopHomeURL().'cl=clSecupayAjaxController&fnc=status';

			return $sReturn;
		}

		public function fetchSecupayStatus(string $sHash, string $sApiKey)
		{
			if(!$sHash || !$sApiKey)
			{
				Logger::log($this->isLoggingEnabled(), 'SecupayList fetchSecupayStatus: empty hash and/or api key');
				return false;
			}

			$oApi = new SecupayApi(['apikey' => $sApiKey, 'hash' => $sHash], 'status');
			if(($oResponse = $oApi->request()))
			{
				if($oResponse->checkResponse() && ($oData = $oResponse->getData()))
				{
					if(property_exists($oData, 'status') && ($sStatus = $oData->status))
					{
						if(property_exists($oData, 'status_description') && ($sStatusDescription = $oData->status_description) && (strlen($sStatusDescription) > 2))
						{
							Logger::log($this->isLoggingEnabled(), 'SecupayList fetchSecupayStatus: new status', 'hash: '.$sHash, 'status: '.$sStatusDescription);
							Table::createStatusEntry($sHash, '', $sStatusDescription, $this->isLoggingEnabled());
						}

						if(property_exists($oData, 'trans_id') && ($sTransactionId = $oData->trans_id))
						{
							Logger::log($this->isLoggingEnabled(), 'SecupayList fetchSecupayStatus: update transaction id', 'hash: '.$sHash, 'transaction id: '.$sTransactionId);
							Table::setTransactionId($sHash, $sTransactionId, $this->isLoggingEnabled());
						}
					}
					else
						Logger::log($this->isLoggingEnabled(), 'SecupayList fetchSecupayStatus: missing status in response', $oResponse);
				}
				else
					Logger::log($this->isLoggingEnabled(), 'SecupayList fetchSecupayStatus: response check failed/no data', $oResponse);
			}
			else
				Logger::log($this->isLoggingEnabled(), 'SecupayList fetchSecupayStatus: api request failed', $oApi);

			DatabaseProvider::getDb()->Execute('UPDATE oxsecupay SET updated = 1 WHERE `hash` = ?;', [ $sHash ]);
		}

		public function isLoggingEnabled() : bool
		{
			return $this->blLoggingEnabled;
		}
		public function setLoggingEnabled(bool $blLoggingEnabled)
		{
			$this->blLoggingEnabled = $blLoggingEnabled;
		}
	}
}