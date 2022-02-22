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
	use \Secupay\Payment\Core\Codec;
	use \Secupay\Payment\Core\PaymentTypes;
	use \Secupay\Payment\Core\Table;

	use \OxidEsales\Facts\Facts;

	use \OxidEsales\Eshop\Core\DatabaseProvider;
	use \OxidEsales\Eshop\Core\Registry;
	use \OxidEsales\Eshop\Core\ShopVersion;

	use \OxidEsales\Eshop\Core\Exception\ArticleInputException;
	use \OxidEsales\Eshop\Core\Exception\NoArticleException;
	use \OxidEsales\Eshop\Core\Exception\OutOfStockException;

	use \OxidEsales\Eshop\Application\Model\Order;
	use \OxidEsales\Eshop\Application\Model\User;

	use \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
	use \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleConfigurationDaoBridgeInterface;

	class OrderController extends OrderController_parent
	{
		const ORDER_STATE_SECUPAY_OK     = 'secupayOK';
		const ORDER_STATE_SECUPAY_CANCEL = 'secupayCancel';
		const ORDER_STATE_SECUPAY_ERROR  = 'secupayError';

		private $aSecupayPaymentTypes = null;
		private $blLoggingEnabled = false;

		public function __construct()
		{
			$this->setLoggingEnabled(boolval(Registry::getConfig()->getConfigParam('secupay_blDebug_log')));
		}

		/**
		 * Returns next order step. If ordering was sucessfull - returns string "thankyou" (possible
		 * additional parameters), otherwise - returns string "payment" with additional
		 * error parameters.
		 *
		 * @param mixed $mSuccess status code
		 *
		 * @return  string  $sNextStep  partial parameter url for next step
		 */
		protected function getNextStep($mSuccess)
		{
			return $this->_getNextStep($mSuccess);
		}

		/**
		 * Returns next order step. If ordering was sucessfull - returns string "thankyou" (possible
		 * additional parameters), otherwise - returns string "payment" with additional
		 * error parameters.
		 *
		 * @param mixed $mSuccess status code
		 *
		 * @return  string  $sNextStep  partial parameter url for next step
		 * @deprecated underscore prefix violates PSR12, will be renamed to "getNextStep" in next major
		 */
		protected function _getNextStep($mSuccess) // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
		{
			if(($oPayment = $this->getPayment()) && ($sPaymentId = $oPayment->getId()))
			{
				Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, getNextStep', 'payment_id: '.$sPaymentId);

				if($oPaymentType = PaymentTypes::getPaymentType($sPaymentId))
				{
					Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, getNextStep', 'success status code: '.$mSuccess);

					$oLanguage = Registry::getLang();
					$oSession = Registry::getSession();

					switch($mSuccess)
					{
						case self::ORDER_STATE_SECUPAY_OK:

							Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, getNextStep', 'success status code: '.$mSuccess);

							try
							{
								$oBasket = $oSession->getBasket();
								$oUser = $this->getUser();

								$oOrder = oxNew(Order::class);
								$mSuccess = $oOrder->finalizeOrder($oBasket, $oUser);

								// update order number in secupay table
								$sHash = $oSession->getVariable('secupay_hash');
								$this->updateOrderNumber($sHash);

								// performing special actions after user finishes order (assignment to special user groups)
								$oUser->onOrderExecute($oBasket, $mSuccess);

								// proceeding to next view
								return $this->getNextStep($mSuccess);
							}
							catch(OutOfStockException $oException)
							{
								Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, getNextStep', 'secupayOK - OutOfStockException:', $oException->getMessage(), $oException->getTraceAsString());

								if(!$this->cancelSecupayTransaction($sHash, 'caused by error: OutOfStock'))
									Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, getNextStep', 'secupayOK - cancel failed');

								$oException->setDestination('basket');
								Registry::getUtilsView()->addErrorToDisplay($oException, false, true, 'basket');
								$mSuccess = $oLanguage->translateString('SECUPAY_ORDER_ERROR_MESSAGE');
							}
							catch(NoArticleException $oException)
							{
								Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, getNextStep', 'secupayOK - NoArticleException:', $oException->getMessage(), $oException->getTraceAsString());

								if(!$this->cancelSecupayTransaction($sHash, 'caused by error: NoArticle'))
									Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, getNextStep', 'secupayOK - cancel failed');

								Registry::getUtilsView()->addErrorToDisplay($oException);
								$mSuccess = $oLanguage->translateString( 'SECUPAY_ORDER_ERROR_MESSAGE' );
							}
							catch(ArticleInputException $oException)
							{
								Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, getNextStep', 'secupayOK - ArticleInputException:', $oException->getMessage(), $oException->getTraceAsString());

								if(!$this->cancelSecupayTransaction($sHash, 'caused by error: ArticleInputException'))
									Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, getNextStep', 'secupayOK - cancel failed');

								Registry::getUtilsView()->addErrorToDisplay($oException);
								$mSuccess = $oLanguage->translateString( 'SECUPAY_ORDER_ERROR_MESSAGE' );
							}
							break;

						case self::ORDER_STATE_SECUPAY_CANCEL:
							$mSuccess = $oLanguage->translateString('SECUPAY_PAYMENT_CANCEL_MESSAGE');
							break;

						case self::ORDER_STATE_SECUPAY_ERROR:
							$mSuccess = $oLanguage->translateString('SECUPAY_PAYMENT_ERROR_MESSAGE');
							break;
					}
				}
			}
	        return parent::_getNextStep($iSuccess);
		}

		/**
		* Checks for order rules confirmation ("ord_agb", "ord_custinfo" form values)(if no
		* rules agreed - returns to order view), loads basket contents (plus applied
		* price/amount discount if available - checks for stock, checks user data (if no
		* data is set - returns to user login page). Stores order info to database
		* (\OxidEsales\Eshop\Application\Model\Order::finalizeOrder()). According to sum for items automatically assigns
		* user to special user group ( \OxidEsales\Eshop\Application\Model\User::onOrderExecute(); if this option is not
		* disabled in admin). Finally you will be redirected to next page (order::_getNextStep()).
		*
		* @return string
		*/
		public function execute()
		{
			$sPaymentId = $this->getPayment()->getId();
			$oPaymentType = PaymentTypes::getPaymentType($sPaymentId);

			if($oPaymentType)
			{
				Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, execute', 'payment id: '.$sPaymentId, 'payment type:'.$oPaymentType->getType());

				if(!$this->getSession()->checkSessionChallenge())
					return;

				if(!$this->_validateTermsAndConditions())
				{
					$this->_blConfirmAGBError = 1;
					return;
				}

				// additional check if we really really have a user now
				$oUser = $this->getUser();
				if(!$oUser)
					return 'user';

				// get basket contents
				$oBasket = $this->getSession()->getBasket();
				if($oBasket->getProductsCount())
				{
					try
					{
						$sOrderId = $oBasket->getOrderId();

						$oOrder = oxNew(Order::class);
						$oOrder->load($sOrderId);

						Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, execute', 'order id: '.$sOrderId);

						if($iOrderState = $oOrder->validateOrder($oBasket, $oUser))
							return $iOrderState;

						$mSuccess = $this->createSecupayTransaction($oOrder);

						// proceeding to next view
						return $this->getNextStep($mSuccess);
					}
					catch(OutOfStockException $oException)
					{
						$oException->setDestination('basket');
						Registry::getUtilsView()->addErrorToDisplay($oException, false, true, 'basket');
					}
					catch(NoArticleException $oException)
					{
						Registry::getUtilsView()->addErrorToDisplay($oException);
					}
					catch(ArticleInputException $oException)
					{
						Registry::getUtilsView()->addErrorToDisplay($oException);
					}
				}
			}
			return parent::execute();
		}

		// TODO check usage
		public function getSecupayOrderHash() : ?string
		{
			$sOrderId = $this->getOrderId();

			Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, getSecupayOrderHash', 'order id: '.$sOrderId);
			
			if($sOrderId)
			{
				$oDB = DatabaseProvider::getDb();
				$sQuery = 'SELECT `hash` FROM oxsecupay WHERE oxorder_id = '.$oDB->quote($sOrderId).' ORDER BY created DESC LIMIT 1;';
				Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, getSecupayOrderHash', $sQuery);

				if($sHash = $oDB->getOne($sQuery))
				{
					Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, getSecupayOrderHash', 'hash: '.$sHash);
					return $sHash;
				}
			}
			Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, getSecupayOrderHash', 'no hash found in database');
			return false;
		}

		/**
		 * Function to which user is redirected from iframe if payment is successfull
		 */
		public function processSecupaySuccess()
		{
			$mSuccess = 'secupayError';
			Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, processSecupaySuccess');

			$sHash = $this->getSession()->getVariable('secupay_hash');
			$this->getSession()->deleteVariable('secupay_iframe_url');
			$this->_sThisTemplate = '/page/checkout/order.tpl';

			Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, processSecupaySuccess', 'hash: '.$sHash);

			if($sHash && $this->validateSecupayTransaction($sHash))
			{
				$mSuccess = 'secupayOK';
				$this->getSession()->setVariable('secupay_success', true);
			}
			else
				$this->getSession()->setVariable('secupay_success', false);

			return $this->getNextStep($mSuccess);
		}

		/**
		 * Function to which user is redirected from iframe if payment failed
		 */
		public function processSecupayFailure()
		{
			Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, processSecupayFailure');
			$this->getSession()->deleteVariable('secupay_iframe_url');
			$this->_sThisTemplate = '/page/checkout/order.tpl';
			$this->getSession()->setVariable('secupay_success', false);
			return $this->getNextStep('secupayCancel');
		}

		/**
		 * This function displays secupay Iframe for current session
		 */
		public function processSecupayIframe()
		{
			Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, processSecupayIframe');

			// change current template to template that displays iframe
			$this->_sThisTemplate = 'secupay/secupay_iframe.tpl';
		}

		/**
		 * Function that returns the Iframe Url for current session
		 * Function has to be public, or the template will not be able to call it
		 *
		 * @return string IframeUrl
		 */
		public function getSecupayIframeUrl() : ?string
		{
			// get iframe url from session
			$sIframeURL = $this->getSession()->getVariable('secupay_iframe_url');
			if(!$sIframeURL)
			{
				Registry::getUtils()->redirect(Registry::getConfig()->getShopHomeUrl().'cl=order&fnc=processSecupayFailure', true, 302);
				return null;
			}
			return $sIframeURL;
		}

		protected function getOrderId() : ?string
		{
			if(($oBasket = $this->getSession()->getBasket()) && ($sOrderId = $oBasket->getOrderId()))
				return $sOrderId;

			return null;
		}

		protected function getSecupayBasketAmount() : int
		{
			if(($oBasket = $this->getSession()->getBasket()) && ($oPrice = $oBasket->getPrice()))
				return floor($oPrice->getBruttoPrice() * 100.0);

			return 0;
		}

		protected function updateOrderNumber($sHash)
		{
			$sOrderId = $this->getOrderId();
			$oOrder = oxNew(Order::class);

			if($oOrder->load($oID))
			{
				$oOrderNumber = $oOrder->oxorder__oxordernr->value;
				Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, updateOrderNumber', 'order number: '.$oOrderNumber, 'hash: '.$sHash);

				if($oOrderNumber && $sHash)
					DatabaseProvider::getDb()->execute('UPDATE oxsecupay SET oxordernr = ? WHERE `hash` = ?;', [ $oOrderNumber, $sHash ]);
			}
		}

		public function shouldSecupayWarnDelivery() : bool
		{
			if(($oPayment = $this->getPayment()) && ($oPaymentType = PaymentTypes::getPaymentType($oPayment->getId())))
			{
				if(($sDeliveryAddressOptionName = $oPaymentType->getDeliveryAdressOptionName()) && ($this->getConfig()->getConfigParam($sDeliveryAddressOptionName) == '2'))
				{
					$sOrderId = $this->getOrderId();
					$oOrder = oxNew(Order::class);
					$oOrder->load($sOrderId);

					if($oOrder->getDelAddressInfo())
						return true;
				}
			}
			return false;
		}

		protected function validateSecupayTransaction(string $sHash = null) : bool
		{
			Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, validateSecupayTransaction', 'hash: '.$sHash);

			if($sHash)
			{
				$aRequestData = [ 'apikey' => $this->getConfig()->getConfigParam('secupay_api_key'), 'hash' => $sHash, 'apiversion' => SecupayApi::getApiVersion() ];

				$oApi = new SecupayApi($aRequestData, 'status/'.$sHash);
				if($oResponse = $oApi->request())
				{
					if($oResponse->checkResponse())
					{
						if(($oResponseData = $oResponse->getData()))
						{
							if(property_exists($oResponseData, 'status'))
							{
								if(property_exists($oResponseData, 'amount'))
								{
									$iAmountReceived = floor($oResponseData->amount);
									$iAmountBasket = $this->getBasketAmount();

									$blStatusOK = in_array($oResponseData->status, ['accepted', 'authorized', 'scored']);
									$blPrepay = ($sPaymentId = $this->getPayment()->getId()) && ($oPaymentType = PaymentTypes::getPaymentType($sPaymentId)) && ($oPaymentType.getType() == 'prepay');

									if(($blStatusOK || $blPrepay) && ($iAmountReceived == $iAmountBasket))
									{
										if(property_exists($oResponseData, 'trans_id') && $oResponseData->trans_id)
											Table::setTransactionId($sHash, $oResponseData->trans_id, $this->isLoggingEnabled());

										return true;
									}
									else
										Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, validateSecupayTransaction failure!', 'hash: '.$sHash, 'status: '.$oResponseData->status, 'prepay: '.($blPrepay ? 'true' : 'false'), 'ammount received: '.$iAmountReceived, 'ammount basket: '.$iAmountBasket);
								}
								else
									Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, validateSecupayTransaction failure!', 'missing ammount in response: ', $oResponseData, 'hash: '.$sHash);
							}
							else
								Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, validateSecupayTransaction failure!', 'missing status in response: ', $oResponseData, 'hash: '.$sHash);
						}
						else
							Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, validateSecupayTransaction failure!', 'no data in response: ', $oResponseData, 'hash: '.$sHash);
					}
					else
						Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, validateSecupayTransaction failure!', 'check response failed: ', $oResponse, 'hash: '.$sHash);
				}
				else
					Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, validateSecupayTransaction failure!', 'api request failed', 'hash: '.$sHash, $aRequestData);
			}
			return false;
		}

		protected function createSecupayTransaction(Order $oOrder = null)
		{
			try
			{
				$oConfig = $this->getConfig();
				$oSession = $this->getSession();
				$oDB = DatabaseProvider::getDb();

				if(($oBasket = $oSession->getBasket()) && ($sPaymentId = $oBasket->getPaymentId()) && ($oPaymentType = PaymentTypes::getPaymentType($sPaymentId)))
				{
					Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, createSecupayTransaction', 'payment type: '.$oPaymentType->getType());

					$oSession->deleteVariable('sp_err_msg');
					$oSession->deleteVariable('secupay_iframe_url');
					$oSession->deleteVariable('secupay_hash');
					$oSession->deleteVariable('secupay_success');

					$sUserID = $oSession->getVariable('usr');
					Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, createSecupayTransaction', 'user id: '.$sUserID);

					$oUser = oxNew(User::class);
					$oUser->load($sUserID);

					$sOrderId = $oOrder ? $oOrder->getId() : null;
					if(!$sOrderId)
						$sOrderId = $oSession->getVariable('sess_challenge');

					Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, createSecupayTransaction', 'order id: '.$sOrderId);

					$sTitle = '';
					try { $sTitle = Registry::getLang()->translateString($oUser->oxuser__oxsal->value, Registry::getLang()->getTplLanguage(), $this->isAdmin()); }
					catch(LanguageException $oEx) { } // is thrown in debug mode and has to be caught here, as smarty hangs otherwise!

					$aData = [
						'apikey' => $oConfig->getConfigParam('secupay_api_key'),
						'apiversion' => SecupayApi::getApiVersion(),
						'shop' => 'OXID '.oxNew(Facts::class)->getEdition(),
						'shopversion' => ShopVersion::getVersion(),
						'modulversion' => ContainerFactory::getInstance()->getContainer()->get(ModuleConfigurationDaoBridgeInterface::class)->get('Secupay_Payment')->getVersion(),

						'payment_type' => $oPaymentType->getType(),

						'url_success' => $oConfig->getSslShopUrl().'index.php?cl=order&fnc=processSecupaySuccess&sDeliveryAddressMD5='.$this->getDeliveryAddressMD5(),
						'url_failure' => $oConfig->getSslShopUrl().'index.php?cl=order&fnc=processSecupayFailure',
						'url_push' => $oConfig->getSslShopUrl().'index.php?cl=clSecupayPushController',

						'title' => $sTitle,
						'firstname' => $oUser->oxuser__oxfname->value,
						'lastname' => $oUser->oxuser__oxlname->value,
						'company' => $oUser->oxuser__oxcompany->value,
						'name_affix' => $oUser->oxuser__oxaddinfo->value,
						'street' => $oUser->oxuser__oxstreet->value,
						'housenumber' => $oUser->oxuser__oxstreetnr->value,
						'zip' => $oUser->oxuser__oxzip->value,
						'city' => $oUser->oxuser__oxcity->value,
						'country' => $oDB->getOne('SELECT OXISOALPHA2 FROM '.getViewName('oxcountry').' WHERE OXID = ?;', [ $oUser->oxuser__oxcountryid->value ]),
						'telephone' => $oUser->oxuser__oxfon->value,
						'dob' => $oUser->oxuser__oxbirthdate->value,
						'email' => $oUser->oxuser__oxusername->value,
						'ip' => $_SERVER['REMOTE_ADDR'],

						'amount' => floor($oBasket->getPrice()->getBruttoPrice() * 100.0),
						'currency' => $oBasket->getBasketCurrency()->name,
						'purpose' => $this->getSecupayPurpose(),
					];

					if(!$oConfig->getConfigParam('secupay_blMode'))
						$aData['demo'] = 1;

					if(($sLangAbbr = $oLang->getLanguageAbbr($oLang->getTplLanguage())) && ($sLangAbbr === 'en'))
						$aData['language'] = 'en_US';
					else
						$aData['language'] = 'de_DE';

					$iPositiv = 0;
					$iNegativ = 0;
					if($oConfig->getConfigParam('secupay_experience'))
					{
						$oxiduser=$oUser->oxuser__oxid->value;
						$respositiv=$oDB->getOne("SELECT count(oxorder.OXUSERID) FROM oxorder WHERE oxorder.OXPAID != '0000-00-00 00:00:00' AND oxorder.OXTRACKCODE IS NOT NULL AND oxorder.OXUSERID ='$oxiduser'");
						$resnegativ=$oDB->getOne("SELECT count(oxorder.OXUSERID) FROM oxorder WHERE oxorder.OXUSERID = '$oxiduser'");
						$resnegativ=$resnegativ-$respositiv;

						if($respositiv)
						{
							$positiv=$respositiv;
							$negativ=$resnegativ;  
						}
					}
					$aData['experience'] = [
						'positive' => $iPositiv,
						'negative' => $iNegativ,
					];

					$aProducts = [];
					foreach($oBasket->getContents() as $oBasketItem)
					{
						$oProduct = new stdClass();
						$oProduct->article_number = $oBasketItem->getArticle()->getFieldData('oxartnum');
						$oProduct->name = $oBasketItem->getTitle();
						$oProduct->model = $oBasketItem->getArticle()->getFieldData('oxshortdesc');
						$oProduct->ean = $oBasketItem->getArticle()->getFieldData('oxean');
						$oProduct->quantity = $oBasketItem->getAmount();
						$oProduct->price = $oBasketItem->getArticle()->getPrice(1)->getBruttoPrice() * 100;
						$oProduct->total = $oBasketItem->getPrice()->getBruttoPrice() * 100;
						$oProduct->tax = $oBasketItem->getPrice()->getVat();
						$aProducts[] = $oProduct;
					}
					$aData['basket'] = json_encode(Codec::ensureUTF8($aProducts), JSON_UNESCAPED_UNICODE);

					if($oDeliveryAddress = $oOrder->getDelAddressInfo())
					{
						$aData['delivery_address'] = new stdClass();
						$aData['delivery_address']->firstname = $oDeliveryAddress->oxaddress__oxfname->value;
						$aData['delivery_address']->lastname = $oDeliveryAddress->oxaddress__oxlname->value;
						$aData['delivery_address']->street = $oDeliveryAddress->oxaddress__oxstreet->value;
						$aData['delivery_address']->housenumber = $oDeliveryAddress->oxaddress__oxstreetnr->value;
						$aData['delivery_address']->zip = $oDeliveryAddress->oxaddress__oxzip->value;
						$aData['delivery_address']->city = $oDeliveryAddress->oxaddress__oxcity->value;
						$aData['delivery_address']->country = $oDB->getOne('SELECT OXISOALPHA2 FROM '.getViewName('oxcountry').' WHERE OXID = ?', [ $oDeliveryAddress->oxaddress__oxcountryid->value ]);
						$aData['delivery_address']->company = $oDeliveryAddress->oxaddress__oxcompany->value;
					}
					else
					{
						$aData['delivery_address'] = new stdClass();
						$aData['delivery_address']->firstname = $aData['firstname'];
						$aData['delivery_address']->lastname = $aData['lastname'];
						$aData['delivery_address']->street = $aData['street'];
						$aData['delivery_address']->housenumber = $aData['housenumber'];
						$aData['delivery_address']->zip = $aData['zip'];
						$aData['delivery_address']->city = $aData['city'];
						$aData['delivery_address']->country = $aData['country'];
						$aData['delivery_address']->company = $aData['company'];
					}

					if($sOrderId)
					{
						$aData['userfields'] = new stdClass();
						$aData['userfields']->oxorder_id = $sOrderId;
					}

					$aData = Codec::ensureUTF8($aData);
					Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, createSecupayTransaction', 'api request data: ', $aData);

					$oApi = new SecupayApi($aData, 'init');
					$oResponse = $oApi->request();
					Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, createSecupayTransaction', 'api return data: ', $oResponse);

					if($oResponse && $oResponse->checkResponse($this->isLoggingEnabled()))
					{
						$sHash = $oResponse->getHash();

						/* Eintrag in secupay eigene Log Tabelle im Administrationsbereich des Modul */
						Table::createTransactionEntry($aData, json_encode(Codec::ensureUTF8($oResponse), JSON_UNESCAPED_UNICODE), $sHash, $sPaymentId, $sOrderId, $aData['amount'], $oResponse->getIFrameURL(), $this->isLoggingEnabled());
						Table::createStatusEntry($sHash, '', 'erstellt', $this->isLoggingEnabled());

						$oSession->setVariable('secupay_iframe_url', $oResponse->getIFrameURL());
						$oSession->setVariable('secupay_hash', $sHash);

						Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, createSecupayTransaction', 'Anfrage hash '.$sHash.' erfolgreich');
						Registry::getUtils()->redirect($oConfig->getShopSecureHomeUrl().'cl=order&fnc=processSecupayIframe', true, 302);
					}
					else
					{
						$sHash = $oResponse->getHash();

						Table::createTransactionEntry($aData, json_encode(Codec::ensureUTF8($oResponse), JSON_UNESCAPED_UNICODE), $sHash, $sPaymentId, $sOrderId, $aData['amount'], $oResponse->getIFrameURL(), $this->isLoggingEnabled());
						Table::createStatusEntry($sHash, $oResponse->getErrorMessage($this->isLoggingEnabled()), $oResponse->getStatus($this->isLoggingEnabled()), $this->isLoggingEnabled());

						Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, createSecupayTransaction', 'Anfrage hash '.$sHash.' fehlgeschlagen');

						$sErrorMessageUser = $oResponse->getErrorMessageUser($this->isLoggingEnabled());
						if(!$sErrorMessageUser) $sErrorMessageUser = 'Verbindungsfehler';

						$oSession->setVariable('sp_err_msg', $sErrorMessageUser);

						return $sErrorMessageUser;
					}
				}
				else
					Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, createSecupayTransaction', 'Anfrage hash '.$sHash.' fehlgeschlagen');
			}
			catch(\Exception $oException)
			{
				Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, createSecupayTransaction', 'Exception: '.$oException->getMessage(), $oException->getTraceAsString());
			}
		}

		protected function getSecupayPurpose() : ?string
		{
			$sShop = $this->getConfig()->getActiveShop()->oxshops__oxname->getRawValue();
			$sCustomName = $this->getConfig()->getConfigParam('secupay_shopname_ls');

			if(strlen($sCustomName) > 2)
				$sShop = $sCustomName;

			$sPurpose = $sShop.'|'.date('d.m.Y H:i:s').'|Bei Fragen TEL 035955755055';
			Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, getSecupayPurpose', $sPurpose);

			return $sPurpose;
		}

		protected function cancelSecupayTransaction(string $sHash = null, string $sComment = null) : bool
		{
			if($sHash)
			{
				Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, cancelSecupayTransaction', 'hash: '.$sHash);

				$aData = ['apikey' => $this->getConfig()->getConfigParam('secupay_api_key')];

				if($sComment)
					$aData['comment'] = $sComment;

				$oApi = new SecupayApi($aData, $sHash.'/cancel');
				$oResponse = $oApi->request();

				Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, cancelSecupayTransaction', 'api return data: ', $oResponse);

				$sStatus = $oResponse->getStatus();
				if($oResponse && $oResponse->checkResponse($this->isLoggingEnabled()))
				{
					Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, cancelSecupayTransaction - ok');
					Table::createStatusEntry($sHash, 'automat. Storno - '.$sStatus, $oResponse->getStatus($this->isLoggingEnabled()), $this->isLoggingEnabled());
					return true;
				}
				else
				{
					Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, cancelSecupayTransaction - failed');
					Table::createStatusEntry($sHash, 'automat. Storno - '.$sStatus.' - '.$oResponse->getErrorMessage($this->isLoggingEnabled()), $oResponse->getStatus($this->isLoggingEnabled()), $this->isLoggingEnabled());
					Table::setTransactionRank($sHash, 80); // show transaction at top of list
					return false;
				}
			}
			else
				Logger::log($this->isLoggingEnabled(), 'secupay_order_controller, cancelSecupayTransaction - empty hash');

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