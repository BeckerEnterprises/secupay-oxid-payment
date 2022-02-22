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
	class Codec
	{
		public static function seemsUTF8(string $sData = null) : bool
		{
			if($sData)
			{
				for($i = 0; $i < strlen($sData); $i++)
				{
					$n = 0;

					if(ord($sData[$i]) < 0x80) continue;				// 0bbbbbbb
					else if((ord($sData[$i]) & 0xE0) == 0xC0) $n = 1;	// 110bbbbb
					else if((ord($sData[$i]) & 0xF0) == 0xE0) $n = 2;	// 1110bbbb
					else if((ord($sData[$i]) & 0xF8) == 0xF0) $n = 3;	// 11110bbb
					else if((ord($sData[$i]) & 0xFC) == 0xF8) $n = 4;	// 111110bb
					else if((ord($sData[$i]) & 0xFE) == 0xFC) $n = 5;	// 1111110b
					else
						return false; // Does not match any model

					for($j = 0; $j < $n; $j++)
					{
						// n bytes matching 10bbbbbb follow ?
						if((++$i == strlen($sData)) || ((ord($sData[$i]) & 0xC0) != 0x80))
							return false;
					}
				}
			}
			return true;
		}

		public static function ensureUTF8($oData)
		{
			if(is_string($oData))
				return self::seemsUTF8($oData) ? $oData : utf8_encode($oData);

			else if(is_array($oData))
			{
				foreach($oData as $key => $value)
					$oData[$key] = self::ensureUTF8($value);
			}

			else if(is_object($oData))
			{
				foreach($oData as $property => $value)
					$oData->{$property} = self::ensureUTF8($value);
			}

			return $oData;
		}
	}
}