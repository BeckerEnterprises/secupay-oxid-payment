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

namespace Secupay\Payment\Application\Model
{
	use \Secupay\Payment\Core\Logger;
	use \Secupay\Payment\Core\PaymentTypes;

	class PaymentGateway extends PaymentGateway_parent
	{
		/**
		 * Executes payment, returns true on success.
		 *
		 * @param double $dAmount Goods amount
		 * @param object $oOrder  User ordering object
		 *
		 * @return bool
		*/
		public function executePayment($dAmount, &$oOrder)
		{
			$blLoggingEnabled = $this->getConfig()->getConfigParam('secupay_blDebug_log');

			if(($sPaymentId = $this->getSession()->getBasket()->getPaymentId()) && ($oPaymentType = PaymentTypes::getPaymentType($sPaymentId)))
			{
				Logger::log($blLoggingEnabled, 'secupayPaymentGateway executePayment', 'payment type: '.$oPaymentType->getType());

				$blSuccess = $this->getSession()->getVariable('secupay_success');
				$this->getSession()->deleteVariable('secupay_success');

				if($blSuccess)
				{
					Logger::log($blLoggingEnabled, 'secupayPaymentGateway executePayment', 'success');
					return true;
				}
				else
				{
					Logger::log($blLoggingEnabled, 'secupayPaymentGateway executePayment', 'failure');
					return false;
				}
			}
			else
			{
				if($sPaymentId = $this->getSession()->getBasket()->getPaymentId())
					Logger::log($blLoggingEnabled, 'secupayPaymentGateway executePayment', 'no secupay payment id: '.$sPaymentId);
				else
					Logger::log($blLoggingEnabled, 'secupayPaymentGateway executePayment', 'no secupay payment, call parent');

				return parent::executePayment($dAmount, $oOrder);
			}
		}
	}
}