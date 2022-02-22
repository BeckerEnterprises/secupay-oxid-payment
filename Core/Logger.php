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
	use \DateTime;
	use \OxidEsales\Eshop\Core\Registry;

	class Logger
	{
		public static function log(bool $blLoggingEnabled = true)
		{
			if(!$blLoggingEnabled)
				return;

			$sLogDate = (new DateTime())->format('Y-m-d H:i:s');
			$sLogFile = Registry::getConfig()->getLogsDir().'splog.log';

			$aArgs = func_get_args();
			for($it = 1; $it < count($aArgs); $it++)
			{
				if($sLogValue = $aArgs[$it])
				{
					if(!is_string($sLogValue) && !is_numeric($sLogValue))
						$sLogValue = print_r($aArgs[$it], true);

					file_put_contents($sLogFile, '['.$sLogDate.'] '.$sLogValue.PHP_EOL, FILE_APPEND);
				}
			}
		}
	}
}