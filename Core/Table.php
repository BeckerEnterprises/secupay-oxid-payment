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
	use \OxidEsales\Eshop\Core\DatabaseProvider;

	class Table
	{
		public static function createStatusEntry(string $sHash, string $sMessage, string $sStatus, bool $blLoggingEnabled = false)
		{
			$oDB = DatabaseProvider::getDb();
			$sOXSecupayId = intval($oDB->getOne('SELECT oxsecupay_id FROM oxsecupay WHERE `hash` = ?;', [ $sHash ]));

			if($sOXSecupayId)
			{
				$sQuery = 'INSERT INTO oxsecupay_status(oxsecupay_id, status, msg) VALUES('.$oDB->quote($sOXSecupayId).', '.$oDB->quote($sStatus).', '.$oDB->quote($sMessage).')';
				Logger::log($blLoggingEnabled, 'secupay_api_table, createStatusEntry insert: ', $sQuery);
				$oDB->execute($sQuery);

				if($iStatusId = intval($oDB->getOne('SELECT LAST_INSERT_ID();')))
				{
					$sQuery = 'UPDATE oxsecupay SET oxsecupay_status_id = '.$oDB->quote($iStatusId).' WHERE `hash` = '.$oDB->quote($sHash).';';
					Logger::log($blLoggingEnabled, 'secupay_api_table, createStatusEntry update: ', $sQuery);
					$oDB->execute($sQuery);

					return $iStatusId;
				}
			}
			return false;
		}

		public static function createTransactionEntry(string $sRequestData, string $sResponseData, string $sHash, string $sPaymentMethod, string $sOXOrderId, float $fAmount, string $sIFrameURL, bool $blLoggingEnabled = false)
		{
			$oDB = DatabaseProvider::getDb();

			$aValues = [
				'req_data' => $oDB->quote($sRequestData),
				'ret_data' => $oDB->quote($sResponseData),
				'hash' => $oDB->quote($sHash),
				'payment_method' => $oDB->quote($sPaymentMethod),
				'oxorder_id' => $oDB->quote($sOXOrderId),
				'amount' => $fAmount,
				'rank' => '99',
				'created' => 'NOW()',
				'iframe_url_id' => self::getIframeUrlId($sHash, $sIFrameURL, $blLoggingEnabled),
			];

			$sQuery = 'INSERT INTO oxsecupay(`'.implode('`,`', array_keys($aValues)).'`) VALUES('.implode(',', $aValues).');';
			Logger::log($blLoggingEnabled, 'secupay_api_table, createTransactionEntry insert: ', $sQuery);
			$oDB->execute($sQuery);
		}

		public static function getIframeUrlId(string $sHash, string $sIFrameURL, bool $blLoggingEnabled = false) : int
		{
			$oDB = DatabaseProvider::getDb();

			$sURL = trim(str_ireplace($sHash, '', $sIFrameURL));
			$sSelectQuery = 'SELECT iframe_url_id FROM oxsecupay_iframe_url WHERE iframe_url = '.$oDB->quote($sURL).' ORDER BY iframe_url_id DESC LIMIT 1;';
			$sInsertQuery = 'INSERT INTO oxsecupay_iframe_url(iframe_url) VALUES('.$oDB->quote($sURL).');';
			$iIFrameUrlId = intval($oDB->getOne($sSelectQuery));

			if(!$iIFrameUrlId)
			{
				$oDB->execute($sInsertQuery);
				$iIFrameUrlId = intval($oDB->getOne($sSelectQuery));
			}

			if($iIFrameUrlId)
				return $iIFrameUrlId;

			Logger::log($blLoggingEnabled, 'secupay_api_table, getIframeUrlId queries: ', $sSelectQuery, $sInsertQuery);
			return 0;
		}

		public static function setTransactionId(string $sHash, string $sTransactionId, bool $blLoggingEnabled = false) : bool
		{
			if($sHash && $sTransactionId)
			{
				$oDB = DatabaseProvider::getDb();

				$sQuery = 'UPDATE oxsecupay SET transaction_id = '.$oDB->quote($sTransactionId).' WHERE (transaction_id IS NULL OR transaction_id = \'\') AND `hash` = '.$oDB->quote($sHash).';';
				Logger::log($blLoggingEnabled, 'secupay_api_table, setTransactionId queries: ', $sQuery);
				$oDB->execute($sQuery);

				return true;
			}

			Logger::log($blLoggingEnabled, 'secupay_api_table, setTransactionId: hash or transaction_id empty');
			return false;
		}

		public static function setTransactionRank(string $sHash, int $iRank, bool $blLoggingEnabled = false) : bool
		{
			if($sHash)
			{
				$oDB = DatabaseProvider::getDb();

				$sQuery = 'UPDATE oxsecupay SET rank = '.$iRank.' WHERE `hash` = '.$oDB->quote($sHash).';';
				Logger::log($blLoggingEnabled, 'secupay_api_table, setTransactionRank queries: ', $sQuery);
				$oDB->execute($sQuery);

				return true;
			}

			Logger::log($blLoggingEnabled, 'secupay_api_table, setTransactionRank: hash empty');
			return false;
		}        
	}
}