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

namespace Secupay\Payment\Application\Controller\Admin
{
	use \OxidEsales\Eshop\Core\DatabaseProvider;

	class OrderList extends OrderList_parent
	{
		/**
		 * Executes parent method parent::render() and returns name of template
		 * file "order_list.tpl".
		 *
		 * @return string
		 */
		public function render()
		{
			$blLoggingEnabled = $this->getConfig()->getConfigParam('secupay_blDebug_log');
			Logger::log($blLoggingEnabled, 'secupay OrderList, render');
			$this->secupayAutoSend();

			return parent::render();
		}

		public function secupayAutoSend()
		{
			$blLoggingEnabled = $this->getConfig()->getConfigParam('secupay_blDebug_log');
			$blActiveAutoSend = $this->getConfig()->getConfigParam('secupay_invoice_autosend');
			$blActiveTrackingAutoSend = $this->getConfig()->getConfigParam('secupay_tracking_autosend');
			$blActiveAutoInvoice = $this->getConfig()->getConfigParam('secupay_invoice_autoinvoice');
			Logger::log($blLoggingEnabled, 'secupay OrderList, secupayAutoSend', 'active auto send: '.($blActiveAutoSend ? 'true' : 'false'), 'active tracking auto send: '.($blActiveTrackingAutoSend ? 'true' : 'false'), 'active auto invoice: '.($blActiveAutoInvoice ? 'true' : 'false'));

			if($blActiveAutoSend || $blActiveTrackingAutoSend)
			{
				$oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
				foreach($oDB->getAll('SELECT o.OXORDERNR, o.OXSENDDATE, sp.`hash`, o.OXBILLNR, o.OXTRACKCODE, o.OXDELTYPE, sp.payment_method FROM '.getViewName('oxorder').' o INNER JOIN oxsecupay sp ON sp.oxordernr = o.OXORDERNR AND sp.oxorder_id = o.OXID WHERE sp.v_send IS NULL AND o.OXSENDDATE IS NOT NULL AND o.OXSENDDATE <> \'0000-00-00 00:00:00\' AND o.OXTRACKCODE IS NOT NULL;') as $row)
				{
					$aData = [
						'apikey' => $this->getConfig()->getConfigParam('secupay_api_key'),
						'hash' => $row['hash'],
						'invoice_number' => $blActiveAutoInvoice ? $row['OXBILLNR'] : $row['OXORDERNR'],
						'tracking' => [
							'provider' => $this->getCarrier($row['OXTRACKCODE'], $row['OXDELTYPE']),
							'number' => $row['OXTRACKCODE'],
						],
					];

					$oApi = new SecupayApi($aData, $row['payment_method'] == 'secupay_invoice' ? 'capture' : 'adddata');
					if($oApi && ($oResponse = $oApi->request()))
					{
						$oResponseData = $oResponse->getData();

						$sTransId = null;
						if($oResponse->getStatus() == 'ok')
						{
							if(property_exists($oResponseData, 'trans_id'))
							{
								Logger::log($blLoggingEnabled, 'secupay OrderList, secupayAutoSend', 'status ok and trans_id in response', $oResponse);
								$sTransId = $oResponse->getData()->trans_id;
							}
							else
								Logger::log($blLoggingEnabled, 'secupay OrderList, secupayAutoSend', 'status ok but no trans_id in response', $oResponse);
						}
						else if(property_exists($oResponseData, 'trans_id'))
						{
							Logger::log($blLoggingEnabled, 'secupay OrderList, secupayAutoSend', 'status not ok but trans_id in response', $oResponse);
							$sTransId = $oResponse->getData()->trans_id;
						}
						else
							Logger::log($blLoggingEnabled, 'secupay OrderList, secupayAutoSend', 'status not ok and no trans_id in response', $oResponse);

						if($sTransId)
						{
							$sQuery = 'UPDATE oxsecupay SET v_status = '.$oDB->quote($sTransId).', tracking_code = '.$oDB->quote($row['OXTRACKCODE']).', carrier_code = '.$oDB->quote($row['OXDELTYPE']).', v_send = 1, searchcode = '.$oDB->quote($aData['invoice_number']).' WHERE `hash` = '.$oDB->quote($row['hash']).';';
							Logger::log($blLoggingEnabled, 'secupay OrderList, secupayAutoSend', 'updating table', $sQuery);
							$oDB->execute($sQuery);
						}
					}
					else
						Logger::log($blLoggingEnabled, 'secupay OrderList, secupayAutoSend', 'api request failed', 'method: '.($row['payment_method'] == 'secupay_invoice' ? 'capture' : 'adddata'), $aData);
				}
			}
		}

		public function getCarrier($sTrackingCode, $sProvider)
		{
			if(preg_match('/^1Z\s?[0-9A-Z]{3}\s?[0-9A-Z]{3}\s?[0-9A-Z]{2}\s?[0-9A-Z]{4}\s?[0-9A-Z]{3}\s?[0-9A-Z]$/i', $sTrackingCode))
				return 'UPS';
			else if(preg_match('/^\d{14}$/', $sTrackingCode))
				return 'HLG';
			else if(preg_match('/^\d{11}$/', $sTrackingCode))
				return 'GLS';
			else if(preg_match('/[A-Z]{3}\d{2}\.?\d{2}\.?(\d{3}\s?){3}/', $sTrackingCode) || preg_match('/[A-Z]{3}\d{2}\.?\d{2}\.?\d{3}/', $sTrackingCode) || preg_match('/(\d{12}|\d{16}|\d{20})/', $sTrackingCode))
				return 'DHL';
			else if(preg_match('/RR\s?\d{4}\s?\d{5}\s?\d(?=DE)/', $sTrackingCode) || preg_match('/NN\s?\d{2}\s?\d{3}\s?\d{3}\s?\d(?=DE(\s)?\d{3})/', $sTrackingCode) || preg_match('/RA\d{9}(?=DE)/', $sTrackingCode) || preg_match('/LX\d{9}(?=DE)/', $sTrackingCode) || preg_match('/LX\s?\d{4}\s?\d{4}\s?\d(?=DE)/', $sTrackingCode) || preg_match('/LX\s?\d{4}\s?\d{4}\s?\d(?=DE)/', $sTrackingCode) || preg_match('/XX\s?\d{2}\s?\d{3}\s?\d{3}\s?\d(?=DE)/', $sTrackingCode) || preg_match('/RG\s?\d{2}\s?\d{3}\s?\d{3}\s?\d(?=DE)/', $sTrackingCode))
				return 'DPAG';

			return $sProvider;
		}
	}
}