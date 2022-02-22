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

	use \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
	use \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleSettingBridgeInterface;

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
				if(intval($oDB->getOne('SELECT COUNT(*) FROM oxpayments WHERE OXID = ?;', [ $oPaymentType->getId() ])) == 0)
				{
					if(!$oPaymentType->getDescription())
					{
						Logger::log(self::$loggingEnabled, 'description missing for '.$oPaymentType->getType());
						continue;
					}

					$none = $oDB->quote('');
					$aData = [
						'OXID' => $oDB->quote($oPaymentType->getId()),
						'OXACTIVE' => 1,
						'OXDESC' => $oDB->quote($oPaymentType->getDescription()),
						'OXADDSUM' => 0,
						'OXADDSUMTYPE' => $oDB->quote('abs'),
						'OXFROMBONI' => 0,
						'OXFROMAMOUNT' => 0,
						'OXTOAMOUNT' => 1000000,
						'OXVALDESC' => $none,
						'OXCHECKED' => 0,
						'OXDESC_1' => $oDB->quote($oPaymentType->getDescription()),
						'OXVALDESC_1' => $none,
						'OXDESC_2' => $none,
						'OXVALDESC_2' => $none,
						'OXDESC_3' => $none,
						'OXVALDESC_3' => $none,
						'OXLONGDESC' => $none,
						'OXLONGDESC_1' => $none,
						'OXLONGDESC_2' => $none,
						'OXLONGDESC_3' => $none,
						'OXSORT' => 0,
					];

					$sQuery = 'INSERT INTO oxpayments(`'.implode('`,`', array_keys($aData)).'`) VALUES('.implode(',', $aData).');';
					Logger::log(self::$loggingEnabled, 'db create '.$oPaymentType->getType(), $sQuery);
					$oDB->execute($sQuery);
				}

				if(in_array($oPaymentType->getType(), $aActivePaymentTypes))
				{
					$sQuery = 'UPDATE oxpayments SET OXACTIVE = 1 WHERE OXID = '.$oDB->quote($oPaymentType->getId()).';';
					Logger::log(self::$loggingEnabled, 'db activate '.$oPaymentType->getType(), $sQuery);
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
							Logger::log(self::$loggingEnabled, 'adding missing column '.$table.'.'.$column, $sQuery);
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

					Logger::log(self::$loggingEnabled, 'creating table '.$table, $sQuery);
					$oDB->execute($sQuery);
				}
			}

			// update views
			Logger::log(self::$loggingEnabled, 'update views');
			oxNew(DbMetaDataHandler::class)->updateViews();

			Logger::log(self::$loggingEnabled, 'secupay_events, onActivate done');
		}

		public static function onDeactivate()
		{
			Logger::log(self::$loggingEnabled, 'secupay_events, onDeactivate');

			$oDB = DatabaseProvider::getDb();

			$moduleSettingBridge = ContainerFactory::getInstance()->getContainer()->get(ModuleSettingBridgeInterface::class);
			$clearSettings = $moduleSettingBridge->get('blSecupayPaymentDeactivateClearSettings', 'Secupay_Payment');
			$removePaymentMethods = $moduleSettingBridge->get('blSecupayPaymentDeactivateRemovePaymentMethods', 'Secupay_Payment');

			if($clearSettings)
			{
				Logger::log(self::$loggingEnabled, 'clear settings');
				$moduleSettingBridge->save('iSecupayPaymentMode', '0', 'Secupay_Payment');
				$moduleSettingBridge->save('sSecupayPaymentApiKey', '', 'Secupay_Payment');
				$moduleSettingBridge->save('blSecupayPaymentDebug', 'true', 'Secupay_Payment');
				$moduleSettingBridge->save('iSecupayPaymentExperience', '1', 'Secupay_Payment');
				$moduleSettingBridge->save('iSecupayPaymentInvoiceTAutoSend', '1', 'Secupay_Payment');
				$moduleSettingBridge->save('blSecupayPaymentDebitActive', 'true', 'Secupay_Payment');
				$moduleSettingBridge->save('sSecupayPaymentDebitShopname', '', 'Secupay_Payment');
				$moduleSettingBridge->save('iSecupayPaymentDebitDeliveryAddress', '0', 'Secupay_Payment');
				$moduleSettingBridge->save('blSecupayPaymentCreditCardActive', 'true', 'Secupay_Payment');
				$moduleSettingBridge->save('iSecupayPaymentCreditCardDeliveryAddress', '0', 'Secupay_Payment');
				$moduleSettingBridge->save('blSecupayPaymentPrePayActive', 'true', 'Secupay_Payment');
				$moduleSettingBridge->save('blSecupayPaymentInvoiceActive', 'true', 'Secupay_Payment');
				$moduleSettingBridge->save('iSecupayPaymentInvoiceDeliveryAddress', '0', 'Secupay_Payment');
				$moduleSettingBridge->save('iSecupayPaymentInvoiceAutoInvoice', '1', 'Secupay_Payment');
				$moduleSettingBridge->save('iSecupayPaymentInvoiceAutoSend', '1', 'Secupay_Payment');
				$moduleSettingBridge->save('blSecupayPaymentDeactivateClearSettings', 'false', 'Secupay_Payment');
				$moduleSettingBridge->save('blSecupayPaymentDeactivateRemovePaymentMethods', 'false', 'Secupay_Payment');
			}

			$aPaymentTypes = PaymentTypes::getPaymentTypes();
			foreach($aPaymentTypes as $oPaymentType)
			{
				if($removePaymentMethods)
				{
					$sQuery = 'DELETE FROM oxpayments WHERE OXID = '.$oDB->quote($oPaymentType->getId()).';';
					Logger::log(self::$loggingEnabled, 'db remove '.$oPaymentType->getType(), $sQuery);
					$oDB->execute($sQuery);
				}
				else
				{
					$sQuery = 'UPDATE oxpayments SET OXACTIVE = 0 WHERE OXID = '.$oDB->quote($oPaymentType->getId()).';';
					Logger::log(self::$loggingEnabled, 'db disable '.$oPaymentType->getType(), $sQuery);
					$oDB->execute($sQuery);
				}
			}

			// update views
			Logger::log(self::$loggingEnabled, 'update views');
			oxNew(DbMetaDataHandler::class)->updateViews();

			Logger::log(self::$loggingEnabled, 'secupay_events, onDeactivate done');
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