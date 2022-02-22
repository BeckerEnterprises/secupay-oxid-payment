<?php
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


/**
* Metadata version
*/
$sMetadataVersion = '2.1';

/**
* Module information
*/
$aModule = [
	'id' => 'Secupay_Payment',
	'title' => [
		'de' => 'secupay Zahlmodul',
		'en' => 'secupay payment module',
	],
	'description' => [
		'de' => 'secupay einfach.sicher.zahlen',
		'en' => 'secupay safe online payments',
	],
	'thumbnail' => 'out/pictures/secupay-logo.png',
	'version' => '3.0.1',
	'author' => 'secupay AG<br><small>Becker Enterprises [info@becker.enterprises] (OXID 6 Migration)</small>',
	'url' => 'https://www.secupay.ag/',
	'email' => 'info@secupay.ag',
	'settings' => [
		[
			'group' => 'Settings_SecupayPayment_Main',
			'name'  => 'iSecupayPaymentMode',
			'type'  => 'select',
			'value' => '0',
			'constraints' => '0|1',
		],
		[
			'group' => 'Settings_SecupayPayment_Main',
			'name'  => 'sSecupayPaymentApiKey',
			'type'  => 'str',
			'value' => '',
		],
		[
			'group' => 'Settings_SecupayPayment_Main',
			'name'  => 'blSecupayPaymentDebug',
			'type'  => 'bool',
			'value' => 'true',
		],
		[
			'group' => 'Settings_SecupayPayment_Main',
			'name'  => 'iSecupayPaymentExperience',
			'type'  => 'select',
			'value' => '1',
			'constraints' => '0|1',
		],
		[
			'group' => 'Settings_SecupayPayment_Main',
			'name'  => 'iSecupayPaymentInvoiceTAutoSend',
			'type'  => 'select',
			'value' => '1',
			'constraints' => '0|1',
		],
		[
			'group' => 'Settings_SecupayPayment_Debit',
			'name'  => 'blSecupayPaymentDebitActive',
			'type'  => 'bool',
			'value' => 'true',
		],
		[
			'group' => 'Settings_SecupayPayment_Debit',
			'name'  => 'sSecupayPaymentDebitShopname',
			'type'  => 'str',
			'value' => '',
		],
		[
			'group' => 'Settings_SecupayPayment_Debit',
			'name'  => 'iSecupayPaymentDebitDeliveryAddress',
			'type'  => 'select',
			'value' => '0',
			'constraints' => '0|1|2',
		],
		[
			'group' => 'Settings_SecupayPayment_CreditCard',
			'name'  => 'blSecupayPaymentCreditCardActive',
			'type'  => 'bool',
			'value' => 'true',
		],
		[
			'group' => 'Settings_SecupayPayment_CreditCard',
			'name'  => 'iSecupayPaymentCreditCardDeliveryAddress',
			'type'  => 'select',
			'value' => '0',
			'constraints' => '0|1|2',
		],
		[
			'group' => 'Settings_SecupayPayment_PrePay',
			'name'  => 'blSecupayPaymentPrePayActive',
			'type'  => 'bool',
			'value' => 'true',
		],
		[
			'group' => 'Settings_SecupayPayment_Invoice',
			'name'  => 'blSecupayPaymentInvoiceActive',
			'type'  => 'bool',
			'value' => 'true',
		],
		[
			'group' => 'Settings_SecupayPayment_Invoice',
			'name'  => 'iSecupayPaymentInvoiceDeliveryAddress',
			'type'  => 'select',
			'value' => '0',
			'constraints' => '0|1|2',
		],
		[
			'group' => 'Settings_SecupayPayment_Invoice',
			'name'  => 'iSecupayPaymentInvoiceAutoInvoice',
			'type'  => 'select',
			'value' => '1',
			'constraints' => '0|1',
		],
		[
			'group' => 'Settings_SecupayPayment_Invoice',
			'name'  => 'iSecupayPaymentInvoiceAutoSend',
			'type'  => 'select',
			'value' => '1',
			'constraints' => '0|1',
		],
		[
			'group' => 'Settings_SecupayPayment_Deactivate',
			'name'  => 'blSecupayPaymentDeactivateClearSettings',
			'type'  => 'bool',
			'value' => 'false',
		],
		[
			'group' => 'Settings_SecupayPayment_Deactivate',
			'name'  => 'blSecupayPaymentDeactivateRemovePaymentMethods',
			'type'  => 'bool',
			'value' => 'false',
		],
	],
    'controllers' => [
		'clSecupay' => \Secupay\Payment\Application\Controller\Admin\Secupay::class,
		'clSecupayList' => \Secupay\Payment\Application\Controller\Admin\SecupayList::class,
		'clSecupayMain' => \Secupay\Payment\Application\Controller\Admin\SecupayMain::class,
		'clSecupayLogin' => \Secupay\Payment\Application\Controller\Admin\SecupayLogin::class,
		'clSecupayAjaxController' => \Secupay\Payment\Application\Controller\Admin\SecupayAjax::class,
		'clSecupayPushController' => \Secupay\Payment\Application\Controller\PushController::class,
	],
	'extend' => [
		\OxidEsales\Eshop\Application\Controller\Admin\OrderList::class => \Secupay\Payment\Application\Controller\Admin\OrderList::class,
		\OxidEsales\Eshop\Application\Controller\OrderController::class => \Secupay\Payment\Application\Controller\OrderController::class,
		\OxidEsales\Eshop\Application\Controller\PaymentController::class => \Secupay\Payment\Application\Controller\PaymentController::class,
		\OxidEsales\Eshop\Application\Model\PaymentGateway::class => \Secupay\Payment\Application\Model\PaymentGateway::class,
	],
	'events' => [
		'onActivate'   => '\Secupay\Payment\Core\Events::onActivate',
		'onDeactivate' => '\Secupay\Payment\Core\Events::onDeactivate',
	],
	'blocks' => [
		[
			'template' => 'page/checkout/payment.tpl',
			'block'    => 'select_payment',
			'file'     => 'Application/views/blocks/page/checkout/payment/select_payment.tpl'
		],
		[
			'template' => 'page/checkout/order.tpl',
			'block'    => 'shippingAndPayment',
			'file'     => 'Application/views/blocks/page/checkout/order/shippingAndPayment.tpl'
		],
	],
	'templates' => [
		'secupay/secupay_iframe.tpl'		=> 'Secupay/Payment/Application/views/tpl/secupay_iframe.tpl',
		'secupay/admin/ajaxrequest.tpl'		=> 'Secupay/Payment/Application/views/admin/tpl/ajaxrequest.tpl',
		'secupay/admin/secupay.tpl'			=> 'Secupay/Payment/Application/views/admin/tpl/secupay.tpl',
		'secupay/admin/secupay_list.tpl'	=> 'Secupay/Payment/Application/views/admin/tpl/secupay_list.tpl',
		'secupay/admin/secupay_login.tpl'	=> 'Secupay/Payment/Application/views/admin/tpl/secupay_login.tpl',
		'secupay/admin/secupay_main.tpl'	=> 'Secupay/Payment/Application/views/admin/tpl/secupay_main.tpl',
	],
];