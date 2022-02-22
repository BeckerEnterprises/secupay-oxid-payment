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

$sLangName = 'English';

$aLang = [
	'charset'														=> 'UTF-8',
	'mxSecupaySettings'												=> 'secupay',
	'tbclSecupayMain'												=> 'Info',
	'tbclSecupayLogin'												=> 'secupay Login',
	'SECUPAY_LIST_MENUITEM'											=> 'Base data',
	'SECUPAY_LIST_MENUSUBITEM'										=> 'secupay',
	'SECUPAY_MAIN_TITLE'											=> 'secupay safe online payments',
	'SECUPAY_MAIN_VERSION'											=> 'Version',
	'SECUPAY_MAIN_HOMEPAGE'											=> 'secupay website',
	'SECUPAY_MAIN_HOMEPAGE_URL'										=> 'https://www.secupay.ag',
	'SECUPAY_MAIN_LOGINPAGE'										=> 'secupay account login',
	'SECUPAY_MAIN_LOGINPAGE_URL'									=> 'https://connect.secupay.ag',
	'SECUPAY_MAIN_INFO'												=> 'You can make the settings for secupay in the "Settings" tab under "Extensions" - "Modules" - "Secupay Payment Module".',

	'SHOP_MODULE_GROUP_Settings_SecupayPayment_Main'				=> 'General',
	'SHOP_MODULE_iSecupayPaymentMode'								=> 'Mode',
	'SHOP_MODULE_iSecupayPaymentMode_0'								=> 'Demo',
	'SHOP_MODULE_iSecupayPaymentMode_1'								=> 'Live',
	'SHOP_MODULE_sSecupayPaymentApiKey'								=> 'API Key',
	'SHOP_MODULE_blSecupayPaymentDebug'								=> 'Debug ',
	'SHOP_MODULE_iSecupayPaymentExperience'							=> 'Tell us about your payment experiences with the customer (requires a corresponding activation for the respective contract).',
	'SHOP_MODULE_iSecupayPaymentExperience_0'						=> 'no',
	'SHOP_MODULE_iSecupayPaymentExperience_1'						=> 'yes',
	'SHOP_MODULE_iSecupayPaymentInvoiceTAutoSend'					=> 'Automatic transmission of tracking information.',
	'SHOP_MODULE_iSecupayPaymentInvoiceTAutoSend_0'					=> 'no',
	'SHOP_MODULE_iSecupayPaymentInvoiceTAutoSend_1'					=> 'yes',

	'SHOP_MODULE_GROUP_Settings_SecupayPayment_Debit'				=> 'Direct debit',
	'SHOP_MODULE_blSecupayPaymentDebitActive'						=> 'Direct debit active',
	'SHOP_MODULE_sSecupayPaymentDebitShopname'						=> 'Shop name in purpose (name from the title bar if empty)',
	'SHOP_MODULE_iSecupayPaymentDebitDeliveryAddress'				=> 'Would you like to deactivate the payment method if the delivery address is different? Alternatively, it is possible to display a notice.',
	'SHOP_MODULE_iSecupayPaymentDebitDeliveryAddress_0'				=> 'no',
	'SHOP_MODULE_iSecupayPaymentDebitDeliveryAddress_1'				=> 'yes',
	'SHOP_MODULE_iSecupayPaymentDebitDeliveryAddress_2'				=> 'notice',

	'SHOP_MODULE_GROUP_Settings_SecupayPayment_CreditCard'			=> 'Credit card',
	'SHOP_MODULE_blSecupayPaymentCreditCardActive'					=> 'Credit card active',
	'SHOP_MODULE_iSecupayPaymentCreditCardDeliveryAddress'			=> 'Would you like to deactivate the payment method if the delivery address is different? Alternatively, it is possible to display a notice.',
	'SHOP_MODULE_iSecupayPaymentCreditCardDeliveryAddress_0'		=> 'no',
	'SHOP_MODULE_iSecupayPaymentCreditCardDeliveryAddress_1'		=> 'yes',
	'SHOP_MODULE_iSecupayPaymentCreditCardDeliveryAddress_2'		=> 'notice',

	'SHOP_MODULE_GROUP_Settings_SecupayPayment_PrePay'				=> 'Prepay',
	'SHOP_MODULE_blSecupayPaymentPrePayActive'						=> 'Prepay active',

	'SHOP_MODULE_GROUP_Settings_SecupayPayment_Invoice'				=> 'Invoice',
	'SHOP_MODULE_blSecupayPaymentInvoiceActive'						=> 'Invoice active',
	'SHOP_MODULE_iSecupayPaymentInvoiceDeliveryAddress'				=> 'Would you like to deactivate the payment method if the delivery address is different? Alternatively, it is possible to display a notice.',
	'SHOP_MODULE_iSecupayPaymentInvoiceDeliveryAddress_0'			=> 'no',
	'SHOP_MODULE_iSecupayPaymentInvoiceDeliveryAddress_1'			=> 'yes',
	'SHOP_MODULE_iSecupayPaymentInvoiceDeliveryAddress_2'			=> 'notice',
	'SHOP_MODULE_iSecupayPaymentInvoiceAutoInvoice'					=> 'Submit the invoice number instead of the order number',
	'SHOP_MODULE_iSecupayPaymentInvoiceAutoInvoice_0'				=> 'no',
	'SHOP_MODULE_iSecupayPaymentInvoiceAutoInvoice_1'				=> 'yes',
	'SHOP_MODULE_iSecupayPaymentInvoiceAutoSend'					=> 'Automatic dispatch notification purchase on account. IMPORTANT! The tracking ID must be stored in the database.',
	'SHOP_MODULE_iSecupayPaymentInvoiceAutoSend_0'					=> 'no',
	'SHOP_MODULE_iSecupayPaymentInvoiceAutoSend_1'					=> 'yes',

	'SHOP_MODULE_GROUP_Settings_SecupayPayment_Deactivate'			=> 'Modul deactivation',
	'SHOP_MODULE_blSecupayPaymentDeactivateClearSettings'			=> 'Remove all module settings when deactivating',
	'SHOP_MODULE_blSecupayPaymentDeactivateRemovePaymentMethods'	=> 'Remove all secupay payment methods when deactivating',
];