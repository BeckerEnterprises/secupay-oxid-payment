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
	use \Secupay\Payment\Core\SecupayApi;
	use \Secupay\Payment\Core\PaymentTypes;
	use \Secupay\Payment\Core\Table;
	use \Secupay\Payment\Core\Logger;

	use \OxidEsales\Eshop\Core\DatabaseProvider;
	use \OxidEsales\Eshop\Core\Field;
	use \OxidEsales\Eshop\Core\Registry;
	use \OxidEsales\Eshop\Core\Controller\BaseController;
	use \OxidEsales\Eshop\Application\Model\Order;

	class PushController extends BaseController
	{
		private $aParameters = null;
		private $blLoggingEnabled = false;

		public function render()
		{
			$sResponse = '';
			$this->setLoggingEnabled(boolval(Registry::getConfig()->getConfigParam('blSecupayPaymentDebug')));

			try
			{
				$aParameters = $this->getRequestParameters();
				Logger::log($this->isLoggingEnabled(), 'secupay_push, render', 'HOST: '.SecupayApi::SECUPAY_HOST);

				//check referrer            
				if(array_key_exists('host', $aParameters) && array_key_exists('host', $aParameters['host']) && $aParameters['host']['host'] == SecupayApi::SECUPAY_HOST)
				{
					if($this->validateRequestParameters())
					{
						$oOrder = $this->getOrder();

						if($aParameters['payment_status'] == 'accepted')
						{
							$this->updateTransaction();
							if(isset($oOrder) && $oOrder)
							{
								if($oOrder->oxorder__oxpaid->value == '0000-00-00 00:00:00')
								{
									$oPaymentType = PaymentTypes::getPaymentTypeByType($oOrder->oxorder__oxpaymenttype->value);
									Logger::log($this->isLoggingEnabled(), 'secupay_push, render payment_type: ', $oPaymentType);

									if($oPaymentType)
									{
										$this->setOrderAccepted($oOrder);

										if($oPaymentType->isOnAcceptedSetOrderPaid())
											$this->setOrderPaid($oOrder);

										$sResponse = 'ack=Approved';
										Logger::log($this->isLoggingEnabled(), 'secupay_push, render status: Approved');
									}
									else
									{
										$sResponse = 'ack=Disapproved&error=wrong+payment+type';
										Logger::log($this->isLoggingEnabled(), 'secupay_push, render error: wrong payment type '.$oOrder->oxorder__oxpaymenttype->value);
									}
								}
								else
								{
									$sResponse = 'ack=Approved';
									Logger::log($this->isLoggingEnabled(), 'secupay_push, render status: Approved');
								}
							}
							else
							{
								$sResponse = 'ack=Disapproved&error=no+matching+order+found+for+hash';
								Logger::log($this->isLoggingEnabled(), 'secupay_push, render error: no matching order found for hash '.$aParameters['hash']);
							}
						}
						else if(in_array($aParameters['payment_status'], ['denied', 'issue', 'void', 'authorized', 'pending', 'scored']))
						{
							$this->updateTransaction($aParameters['payment_status']);

							if(isset($oOrder) && $oOrder)
							{
								if($aParameters['payment_status'] != 'authorized')
									$this->setOrderError($oOrder);

								$sResponse = 'ack=Approved';
								Logger::log($this->isLoggingEnabled(), 'secupay_push, render status: Approved');
							}
							else
							{
								$sResponse = 'ack=Disapproved&error=no+matching+order+found+for+hash';
								Logger::log($this->isLoggingEnabled(), 'secupay_push, render error: no matching order found for hash '.$aParameters['hash']);
							}
						}
						else
						{
							$sResponse = 'ack=Disapproved&error=payment_status+not+supported';
							Logger::log($this->isLoggingEnabled(), 'secupay_push, render error: payment_status not supported '.$aParameters['payment_status']);
						}
					}
					else
					{
						$sResponse = 'ack=Disapproved&error=request+invalid+data';
						Logger::log($this->isLoggingEnabled(), 'secupay_push, render error: request invalid data');
					}
				}
				else
				{
					$sResponse = 'ack=Disapproved&error=request+invalid';
					Logger::log($this->isLoggingEnabled(), 'secupay_push, render error: host validation failed'.(array_key_exists('host', $aParameters) ? ' for: '.$aParameters['host']['host'] : ''));
				}
				echo $sResponse.'&'.http_build_query($_POST);
			}
			catch(\Exception $e)
			{
				echo 'ack=Disapproved&error=unexpected+error&'.http_build_query($_POST);
				Logger::log($this->isLoggingEnabled(), 'secupay_push, render Exception: '.$e->getMessage(), $e->getTraceAsString());
			}
			// prevent Smarty error
			die();
		}

		private function &getOrder() : ?Order
		{
			$oDB = DatabaseProvider::getDb();
			$aParameters = $this->getRequestParameters();

			$sQuery = 'SELECT oxorder_id FROM oxsecupay WHERE `hash` = '.$oDB->quote($aParameters['hash']).' AND oxordernr IS NOT NULL;';
			Logger::log($this->isLoggingEnabled(), 'secupay_push, getOrder', $sQuery);
			$sID = $oDB->getOne($sQuery);

			if($sID)
			{
				$oOrder = oxNew(Order::class);
				$oOrder->load($sID);

				$oOrderArticles = $oOrder->getOrderArticles();

				if($oOrderArticles && ($oOrderArticles->count() > 0))
					return $oOrder;
				else
				{
					Logger::log($this->isLoggingEnabled(), 'secupay_push, getOrder order has no articles');
					return null;
				}
			}

			Logger::log($this->isLoggingEnabled(), 'secupay_push, getOrder no order found in database matching hash: '.$aParameters['hash']);
			return null;
		}

		private function setOrderPaid(Order $oOrder)
		{
			Logger::log($this->isLoggingEnabled(), 'secupay_push, setOrderPaid', 'oxpaid before: ', $oOrder->oxorder__oxpaid->value);

			if(!$oOrder->oxorder__oxpaid->value || ($oOrder->oxorder__oxpaid->value == '0000-00-00 00:00:00'))
			{
				$oOrder->assign(['oxorder__oxpaid' => date('Y-m-d H:i:s')]);
				$oOrder->save();
				Logger::log($this->isLoggingEnabled(), 'secupay_push, setOrderPaid', 'oxpaid after: ', $oOrder->oxorder__oxpaid->value);
			}
			else
				Logger::log($this->isLoggingEnabled(), 'secupay_push, setOrderPaid', 'oxpaid was already set');
		}

		private function setOrderError(Order $oOrder)
		{
			Logger::log($this->isLoggingEnabled(), 'secupay_push, setOrderError');
			$oOrder->oxorder__oxtransstatus = new Field('ERROR');
			$oOrder->oxorder__oxfolder = new Field('ORDERFOLDER_PROBLEMS');
			$oOrder->save();
		}

		private function setOrderAccepted(Order $oOrder)
		{
			Logger::log($this->isLoggingEnabled(), 'secupay_push, setOrderAccepted');
			$oOrder->oxorder__oxtransstatus = new Field('OK');
			$oOrder->oxorder__oxfolder = new Field('ORDERFOLDER_NEW');
			$oOrder->save();
		}

		private function updateTransaction(string $sPaymentStatus = null)
		{
			$aParameters = $this->getRequestParameters();

			$sMessage = 'secupay Status Push - Status: '.$aParameters['payment_status'];
			Table::createStatusEntry($aParameters['hash'], $sMessage, $aParameters['transaction_status'], $this->isLoggingEnabled());

			if($sPaymentStatus && ($sPaymentStatus == 'void'))
				Table::setTransactionRank($aParameters['hash'], 99, $this->isLoggingEnabled());
		}

		protected function getRequestParameters() : array
		{
			if($this->aParameters == null)
			{
				$oConfig = Registry::getConfig();
				$this->aParameters = [
					'hash'					=> $oConfig->getRequestParameter('hash'),
					'api_key'				=> $oConfig->getRequestParameter('apikey'),
					'payment_status'		=> $oConfig->getRequestParameter('payment_status'),
					'comment'				=> $oConfig->getRequestParameter('hint'),
					'transaction_status'	=> $oConfig->getRequestParameter('status_description'),
					'host'					=> parse_url($_SERVER['HTTP_REFERER']),
				];
				Logger::log($this->isLoggingEnabled(), 'secupay_push, getRequestParameters', http_build_query($_POST), $this->aParameters);
			}
			return $this->aParameters;
		}

		protected function validateRequestParameters()
		{
			$oConfig = Registry::getConfig();
			$aParameters = $this->getRequestParameters();

			if(!array_key_exists('hash', $aParameters) || !$aParameters['hash'])
			{
				Logger::log($this->isLoggingEnabled(), 'secupay_push, validateRequestParameters', 'parameter hash not set');
				return false;
			}

			if(!array_key_exists('api_key', $aParameters) || !$aParameters['api_key'])
			{
				Logger::log($this->isLoggingEnabled(), 'secupay_push, validateRequestParameters', 'parameter api_key not set');
				return false;
			}

			if($aParameters['api_key'] != $oConfig->getConfigParam('sSecupayPaymentApiKey'))
			{
				Logger::log($this->isLoggingEnabled(), 'secupay_push, validateRequestParameters', 'api_key missmatch');
				return false;
			}

			if(!array_key_exists('payment_status', $aParameters) || !$aParameters['payment_status'])
			{
				Logger::log($this->isLoggingEnabled(), 'secupay_push, validateRequestParameters', 'parameter payment_status not set');
				return false;
			}

			return true;
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