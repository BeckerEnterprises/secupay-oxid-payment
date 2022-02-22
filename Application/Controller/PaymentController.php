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

namespace Secupay\Payment\Application\Controller
{
	use \Secupay\Payment\Core\PaymentTypes;
	use \Secupay\Payment\Core\Logger;

	use \OxidEsales\Eshop\Core\DatabaseProvider;
	use \OxidEsales\Eshop\Core\Registry;

	use \OxidEsales\Eshop\Application\Model\Order;

	class PaymentController extends PaymentController_parent
	{
		private $aSecupayPaymentTypes = null;
		private $blLoggingEnabled = false;

		public function init()
		{
			$this->setLoggingEnabled(boolval(Registry::getConfig()->getConfigParam('blSecupayPaymentDebug')));
			parent::init();
		}

		/*
		public function isKK()
		{
			return Registry::getConfig()->getConfigParam('blSecupayPaymentCreditCardActive');
		}

		public function isLS()
		{
			return Registry::getConfig()->getConfigParam('blSecupayPaymentDebitActive');
		}
		*/

		public function getDeliveryAdress(int $iType = 0) : bool
		{
			/*
			$oConfig = Registry::getConfig();
			$oBasket = $this->getSession()->getBasket();
			$oID = $oBasket->getOrderId();
			$oOrder = oxNew(Order::class);
			$oOrder->load($oID);
			$oDelAd = $oOrder->getDelAddressInfo();

			if($oDelAd && boolval($oConfig->getConfigParam('secupay_blLieferadresse')))
			{
				switch($iType)
				{
					case 1:
						if(!boolval($oConfig->getConfigParam('secupay_blLastschriftZGActive')))
							return true;
						break;

					case 2:
						if(!boolval($oConfig->getConfigParam('secupay_blKreditkarteZGActive')))
							return true;
						break;

					default:
						return false;
				}
			}
			*/
			return false;
		}

		public function getSecupayPaymentTypes() : array
		{
			if($this->aSecupayPaymentTypes == null)
			{
				$this->aSecupayPaymentTypes = [];

				$oDB = DatabaseProvider::getDb(DatabaseProvider::FETCH_MODE_ASSOC);
				foreach($oDB->getAll('SELECT OXID FROM '.getViewName('oxpayments').' WHERE OXACTIVE = 1;') as $aRow)
				{
					if($oPaymentType = PaymentTypes::getPaymentType($aRow['OXID']))
						$this->aSecupayPaymentTypes[] = $oPaymentType;
				}
			}
			return $this->aSecupayPaymentTypes;
		}

		public function isSecupayPaymentType(string $sPaymentId) : bool
		{
			$aActivePaymentTypes = $this->getSecupayPaymentTypes();
			foreach($aActivePaymentTypes as $oPaymentType)
			{
				if($oPaymentType->getId() == $sPaymentId)
					return true;
			}
			return false;
		}

		public function isSecupayPaymentTypeActive(string $sPaymentId) : bool
		{
			$oConfig = Registry::getConfig();

			$oPaymentType = null;
			foreach($this->getSecupayPaymentTypes() as $oActivePaymentType)
			{
				if($oActivePaymentType->getId() == $sPaymentId)
				{
					$oPaymentType = $oActivePaymentType;
					break;
				}
			}

			if($oPaymentType && ($sOptionName = $oPaymentType->getOptionName()) && $oConfig->getConfigParam($sOptionName))
			{
				$sDeliveryAddressOptionName = $oPaymentType->getDeliveryAdressOptionName();
				if($sDeliveryAddressOptionName && ($oConfig->getConfigParam($sDeliveryAddressOptionName) == '1') && $this->isSecupayDeliveryAdressDifferent())
					return false;

				return true;
			}

			return false;
		}

		public function getPaymentErrorText()
		{
			if(!$this->isSecupayPaymentType($this->getCheckedPaymentId()))
				return parent::getPaymentErrorText();

			$oConfig = Registry::getConfig();
			$sPaymentError = $oConfig->getRequestParameter('payerror');
			$sPaymentErrorMessage = $oConfig->getRequestParameter('payerrortext');
			$sSessionErrorMessage = $this->getSession()->getVariable('sp_err_msg');

			if($sPaymentError && (strlen($sPaymentErrorMessage) > 3))
				return $sPaymentErrorMessage;

			if($sSessionErrorMessage)
			{
				$this->getSession()->deleteVariable('sp_err_msg');
				return $sSessionErrorMessage;
			}

			if(!$sPaymentErrorMessage)
				return 'unknown error';

			return $sPaymentErrorMessage;
		}

		public function isSecupayDeliveryAdressDifferent()
		{
			if($oID = $this->getSession()->getBasket()->getOrderId())
			{
				$oOrder = oxNew(Order::class);
				if($oOrder->load($oID) && $oOrder->getDelAddressInfo())
					return true;
			}
			return false;
		}

		protected function setLoggingEnabled(bool $blLoggingEnabled = false)
		{
			$this->blLoggingEnabled = $blLoggingEnabled;
		}
		protected function isLoggingEnabled() : bool
		{
			return $this->blLoggingEnabled;
		}
	}
}