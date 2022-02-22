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
	use \OxidEsales\Eshop\Core\Registry;
	use \OxidEsales\Eshop\Core\DatabaseProvider;
	use \OxidEsales\Eshop\Core\DbMetaDataHandler;

	class Events
	{
		private static $loggingEnabled = true;

		private static $SECUPAY_TABLES = [
			'oxsecupay' => [
				'columns' => [
					'oxsecupay_id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
					'req_data' => 'text COLLATE latin1_general_ci',
					'ret_data' => 'text COLLATE latin1_general_ci',
					'payment_method' => 'varchar(255) COLLATE latin1_general_ci DEFAULT NULL',
					'transaction_id' => 'int(10) unsigned DEFAULT NULL',
					'oxordernr' => 'int(11) DEFAULT NULL',
					'oxorder_id' => 'char(32) DEFAULT NULL',
					'oxsecupay_status_id' => 'int(10) unsigned DEFAULT NULL',
					'rank' => 'int(10) DEFAULT NULL',
					'amount' => 'varchar(255) COLLATE latin1_general_ci DEFAULT NULL',
					'action' => 'text COLLATE latin1_general_ci',
					'iframe_url_id' => 'int(10) unsigned NOT NULL',
					'updated' => "int(2) unsigned DEFAULT '0'",
					'v_send' => 'int(2) unsigned DEFAULT NULL',
					'v_status' => 'int(10) unsigned DEFAULT NULL',
					'searchcode' => 'varchar(255) COLLATE latin1_general_ci DEFAULT NULL',
					'tracking_code' => 'varchar(255) COLLATE latin1_general_ci DEFAULT NULL',
					'carrier_code' => 'varchar(255) COLLATE latin1_general_ci DEFAULT NULL',
					'hash' => 'varchar(255) DEFAULT NULL',
					'created' => 'datetime NOT NULL',
					'timestamp' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
				],
				'primary' => [ 'oxsecupay_id' ],
				'definition' => [
					'ENGINE' => 'MyISAM',
					'AUTO_INCREMENT' => '1',
					'DEFAULT CHARSET' => 'latin1',
					'COLLATE' => 'latin1_general_ci',
				],
			],
			'oxsecupay_iframe_url' => [
				'columns' => [
					'iframe_url_id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
					'iframe_url' => 'text COLLATE latin1_general_ci',
				],
				'primary' => [ 'iframe_url_id' ],
				'definition' => [
					'ENGINE' => 'MyISAM',
					'AUTO_INCREMENT' => '1',
					'DEFAULT CHARSET' => 'latin1',
					'COLLATE' => 'latin1_general_ci',
				],
			],
			'oxsecupay_status' => [
				'columns' => [
					'oxsecupay_status_id' => 'int(10) unsigned NOT NULL AUTO_INCREMENT',
					'oxsecupay_id' => 'int(10) unsigned NOT NULL',
					'msg' => 'varchar(255) COLLATE latin1_general_ci DEFAULT NULL',
					'status' => 'varchar(255) COLLATE latin1_general_ci DEFAULT NULL',
					'timestamp' => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
				],
				'primary' => [ 'oxsecupay_status_id' ],
				'definition' => [
					'ENGINE' => 'MyISAM',
					'AUTO_INCREMENT' => '1',
					'DEFAULT CHARSET' => 'latin1',
					'COLLATE' => 'latin1_general_ci',
				],
			],
		];

		public static function onActivate()
		{
			Logger::log(self::$loggingEnabled, 'secupay_events, onActivate');

			$oDB = DatabaseProvider::getDb();
			$aPaymentTypes = PaymentTypes::getPaymentTypes();
			$aActivePaymentTypes = PaymentTypes::requestAvailablePaymenttypes(self::$loggingEnabled);

			foreach($aPaymentTypes as $oPaymentType)
			{
				if(intval($oDB->getOne('SELECT COUNT(*) FROM '.getViewName('oxpayments').' WHERE OXID = ?;', [ $oPaymentType->getId() ])) == 0)
				{
					if(!$oPaymentType->getDescription())
					{
						Logger::log(self::$loggingEnabled, 'secupay_events, onActivate', 'description missing for '.$oPaymentType->getType());
						continue;
					}

					$aData = [
						'OXID' => $oDB->quote($oPaymentType->getId()),
						'OXACTIVE' => 1,
						'OXDESC' => $oDB->quote($oPaymentType->getDescription()),
						'OXADDSUM' => 0,
						'OXADDSUMTYPE' => 'abs',
						'OXFROMBONI' => 0,
						'OXFROMAMOUNT' => 0,
						'OXTOAMOUNT' => 1000000,
						'OXVALDESC' => '',
						'OXCHECKED' => 0,
						'OXDESC_1' => $oDB->quote($oPaymentType->getDescription()),
						'OXVALDESC_1' => '',
						'OXDESC_2' => '',
						'OXVALDESC_2' => '',
						'OXDESC_3' => '',
						'OXVALDESC_3' => '',
						'OXLONGDESC' => '',
						'OXLONGDESC_1' => '',
						'OXLONGDESC_2' => '',
						'OXLONGDESC_3' => '',
						'OXSORT' => 0,
					];

					$sQuery = 'INSERT INTO '.getViewName('oxpayments').'(`'.implode('`,`', array_keys($aData)).'`) VALUES('.implode(',', $aData).');';
					Logger::log(self::$loggingEnabled, 'secupay_events, onActivate', 'db create '.$oPaymentType->getType(), $sQuery);
					$oDB->execute($sQuery);
				}

				if(in_array($oPaymentType->getType(), $aActivePaymentTypes))
				{
					$sQuery = 'UPDATE '.getViewName('oxpayments').' SET OXACTIVE = 1 WHERE OXID = '.$oDB->quote($oPaymentType->getId()).';';
					Logger::log(self::$loggingEnabled, 'secupay_events, onActivate', 'db activate '.$oPaymentType->getType(), $sQuery);
					$oDB->execute($sQuery);
				}
			}

			// check table structure
			foreach(self::$SECUPAY_TABLES as $table => $data)
			{
				if(self::tableExists($table))
				{
					foreach($data['columns'] as $column => $column_definition)
					{
						if(!self::columnExists($table, $column))
						{
							$sQuery = 'ALTER TABLE `'.$table.'` ADD COLUMN `'.$column.'` '.$column_definition.';';
							Logger::log(self::$loggingEnabled, 'secupay_events, onActivate', 'adding missing column '.$table.'.'.$column, $sQuery);
							$oDB->execute($sQuery);
						}
					}
				}
				else
				{
					$aColumnsData = [];
					foreach($data['columns'] as $column => $column_definition)
						$aColumnsData[] = '`'.$column.'` '.$column_definition;

					if(array_key_exists('primary', $data) && is_array($data['primary']) && (count($data['primary']) > 0))
						$aColumnsData[] = 'PRIMARY KEY (`'.implode('`,`', $data['primary']).'`)';

					$sQuery = 'CREATE TABLE `'.$table.'`('.implode(',', $aColumnsData).')';

					if(array_key_exists('definition', $data) && is_array($data['definition']))
					{
						foreach($data['definition'] as $table_definition => $table_definition_value)
							$sQuery .= ' '.$table_definition.'='.$table_definition_value;
					}

					$sQuery .= ';';

					Logger::log(self::$loggingEnabled, 'secupay_events, onActivate', 'creating table '.$table, $sQuery);
					$oDB->execute($sQuery);
				}
			}

			// update views
			Logger::log(self::$loggingEnabled, 'secupay_events, onActivate', 'update views');
			oxNew(DbMetaDataHandler::class)->updateViews();

			Logger::log(self::$loggingEnabled, 'secupay_events, onActivate', 'done');
		}

		public static function onDeactivate()
		{
			Logger::log(self::$loggingEnabled, 'secupay_events, onDeactivate');

			$aPaymentTypes = PaymentTypes::getPaymentTypes();
			foreach($aPaymentTypes as $oPaymentType)
			{
				$sQuery = 'UPDATE '.getViewName('oxpayments').' SET OXACTIVE = 0 WHERE OXID = '.$oDB->quote($oPaymentType->getId()).';';
				Logger::log(self::$loggingEnabled, 'secupay_events, onDeactivate', 'db deactivate '.$oPaymentType->getType(), $sQuery);
				$oDB->execute($sQuery);
			}

			// update views
			Logger::log(self::$loggingEnabled, 'secupay_events, onDeactivate', 'update views');
			oxNew(DbMetaDataHandler::class)->updateViews();

			Logger::log(self::$loggingEnabled, 'secupay_events, onDeactivate', 'done');
		}

		private static function tableExists($table)
		{
			return intval(DatabaseProvider::getDb()->getOne('SELECT COUNT(*) FROM information_schema.tables WHERE table_name = ? AND table_schema = DATABASE();', [$table]));
		}

		private static function columnExists($table, $column)
		{
			return intval(DatabaseProvider::getDb()->getOne('SELECT COUNT(*) FROM information_schema.columns WHERE table_name = ? AND column_name = ? AND table_schema = DATABASE();', [$table, $column]));
		}
	}
}