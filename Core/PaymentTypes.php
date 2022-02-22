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
	use \Secupay\Payment\Application\Model\PaymentType;
	use \OxidEsales\Eshop\Core\Registry;

	class PaymentTypes
	{
		private static $paymentTypes = null;

		private static function initPaymentTypes()
		{
			self::$paymentTypes = [];

			$oDebit = oxNew(PaymentType::class);
			$oDebit->setId('secupay_debit');
			$oDebit->setType('debit');
			$oDebit->setOptionName('blSecupayPaymentDebitActive');
			$oDebit->setDescription('secupay.Lastschrift');
			$oDebit->setShortDescription('Lastschrift');
			$oDebit->setOnAcceptedSetOrderPaid(true);
			$oDebit->setCanCheckDeliveryAdress(true);
			$oDebit->setDeliveryAdressOptionName('iSecupayPaymentDebitDeliveryAddress');
			self::$paymentTypes[] = $oDebit;

			$oCreditCard = oxNew(PaymentType::class);
			$oCreditCard->setId('secupay_creditcard');
			$oCreditCard->setType('creditcard');
			$oCreditCard->setOptionName('blSecupayPaymentCreditCardActive');
			$oCreditCard->setDescription('secupay.Kreditkarte');
			$oCreditCard->setShortDescription('Kreditkarte');
			$oCreditCard->setOnAcceptedSetOrderPaid(true);
			$oCreditCard->setCanCheckDeliveryAdress(false);
			$oDebit->setDeliveryAdressOptionName('iSecupayPaymentCreditCardDeliveryAddress');
			self::$paymentTypes[] = $oCreditCard;

			$oPrePay = oxNew(PaymentType::class);
			$oPrePay->setId('secupay_prepay');
			$oPrePay->setType('prepay');
			$oPrePay->setOptionName('blSecupayPaymentPrePayActive');
			$oPrePay->setDescription('secupay.Vorkasse');
			$oPrePay->setShortDescription('Vorkasse');
			$oPrePay->setOnAcceptedSetOrderPaid(true);
			self::$paymentTypes[] = $oPrePay;

			$oDebit = oxNew(PaymentType::class);
			$oDebit->setId('secupay_invoice');
			$oDebit->setType('invoice');
			$oDebit->setOptionName('blSecupayPaymentInvoiceActive');
			$oDebit->setDescription('secupay.Rechnungskauf');
			$oDebit->setShortDescription('Rechnungskauf');
			$oDebit->setOnAcceptedSetOrderPaid(true);
			$oDebit->setCanCheckDeliveryAdress(true);
			$oDebit->setDeliveryAdressOptionName('iSecupayPaymentInvoiceDeliveryAddress');
			self::$paymentTypes[] = $oDebit;
		}

		public static function requestAvailablePaymenttypes($blLoggingEnabled = false)
		{
			$aActivePaymentTypes = [];
			$apikey = Registry::getConfig()->getConfigParam('sSecupayPaymentApiKey');

			if(isset($apikey))
			{
				$oApi = new SecupayApi(['apikey' => $apikey, 'apiversion' => SecupayApi::getApiVersion()], 'gettypes');
				$oResponse = $oApi->request();

				if($oResponse)
				{
					Logger::log($blLoggingEnabled, 'secupayPayment, requestAvailablePaymenttypes', 'api response data', $oResponse);

					if($blCheckResponse = $oResponse->checkResponse($blLoggingEnabled))
					{
						Logger::log($blLoggingEnabled, 'secupayPayment, requestAvailablePaymenttypes', 'api check response', $blCheckResponse);
						$aActivePaymentTypes = $oResponse->getData();
					}
					else
						Logger::log($blLoggingEnabled, 'secupayPayment, requestAvailablePaymenttypes', 'Response Error');
				}
				else
					Logger::log($blLoggingEnabled, 'secupayPayment, requestAvailablePaymenttypes', 'Response Error');
			}
			return $aActivePaymentTypes;
		}

		public static function getPaymentTypes() : array
		{
			if(!self::$paymentTypes)
				self::initPaymentTypes();

			return self::$paymentTypes;
		}

		public static function getPaymentType(string $sPaymentId) : ?PaymentType
		{
			if(!self::$paymentTypes)
				self::initPaymentTypes();

			foreach(self::$paymentTypes as &$paymentType)
			{
				if($paymentType->getId() == $sPaymentId)
					return $paymentType;
			}

			return null;
		}

		public static function getPaymentTypeByType(string $sPaymentType) : ?PaymentType
		{
			if(!self::$paymentTypes)
				self::initPaymentTypes();

			foreach(self::$paymentTypes as &$paymentType)
			{
				if($paymentType->getType() == $sPaymentType)
					return $paymentType;
			}

			return null;
		}

		public static function getId(string $sPaymentType) : ?string
		{
			if(!self::$paymentTypes)
				self::initPaymentTypes();

			foreach(self::$paymentTypes as &$paymentType)
			{
				if($paymentType->getType() == $sPaymentType)
					return $paymentType->getId();
			}

			return null;
		}

		public static function getType(string $sPaymentId) : ?string
		{
			if(!self::$paymentTypes)
				self::initPaymentTypes();

			foreach(self::$paymentTypes as &$paymentType)
			{
				if($paymentType->getId() == $sPaymentId)
					return $paymentType->getType();
			}

			return null;
		}

		public static function getOptionName(string $sPaymentId) : ?string
		{
			if(!self::$paymentTypes)
				self::initPaymentTypes();

			foreach(self::$paymentTypes as &$paymentType)
			{
				if($paymentType->getId() == $sPaymentId)
					return $paymentType->getOptionName();
			}

			return null;
		}

		public static function getDescription(string $sPaymentId) : ?string
		{
			if(!self::$paymentTypes)
				self::initPaymentTypes();

			foreach(self::$paymentTypes as &$paymentType)
			{
				if($paymentType->getId() == $sPaymentId)
					return $paymentType->getDescription();
			}

			return null;
		}

		public static function getShortDescription(string $sPaymentId) : ?string
		{
			if(!self::$paymentTypes)
				self::initPaymentTypes();

			foreach(self::$paymentTypes as &$paymentType)
			{
				if($paymentType->getId() == $sPaymentId)
					return $paymentType->getShortDescription();
			}

			return null;
		}

		public static function isOnAcceptedSetOrderPaid(string $sPaymentId) : bool
		{
			if(!self::$paymentTypes)
				self::initPaymentTypes();

			foreach(self::$paymentTypes as &$paymentType)
			{
				if($paymentType->getId() == $sPaymentId)
					return $paymentType->isOnAcceptedSetOrderPaid();
			}

			return false;
		}

		public static function getDeliveryAdressOptionName(string $sPaymentId) : ?string
		{
			if(!self::$paymentTypes)
				self::initPaymentTypes();

			foreach(self::$paymentTypes as &$paymentType)
			{
				if(($paymentType->getId() == $sPaymentId) && $paymentType->isCanCheckDeliveryAdress())
					return $paymentType->getDeliveryAdressOptionName();
			}

			return null;
		}
	}
}