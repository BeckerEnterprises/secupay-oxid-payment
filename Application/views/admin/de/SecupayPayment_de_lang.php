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

$sLangName = 'Deutsch';

$aLang = [
	'charset'														=> 'UTF-8',
	'mxSecupaySettings'												=> 'secupay',
	'tbclSecupayMain'												=> 'Info',
	'tbclSecupayLogin'												=> 'secupay Login',
	'SECUPAY_LIST_MENUITEM'											=> 'Stammdaten',
	'SECUPAY_LIST_MENUSUBITEM'										=> 'secupay',
	'SECUPAY_MAIN_TITLE'											=> 'secupay - einfach.sicher.zahlen',
	'SECUPAY_MAIN_VERSION'											=> 'Version',
	'SECUPAY_MAIN_HOMEPAGE'											=> 'secupay Webseite',
	'SECUPAY_MAIN_HOMEPAGE_URL'										=> 'https://www.secupay.ag',
	'SECUPAY_MAIN_LOGINPAGE'										=> 'secupay Account Login',
	'SECUPAY_MAIN_LOGINPAGE_URL'									=> 'https://connect.secupay.ag',
	'SECUPAY_MAIN_INFO'												=> 'Sie können die Einstellungen für secupay im Reiter "Einstellungen" unter "Erweiterungen" - "Module" - "secupay Zahlmodul" vornehmen.',

	'SHOP_MODULE_GROUP_Settings_SecupayPayment_Main'				=> 'Allgemeines',
	'SHOP_MODULE_iSecupayPaymentMode'								=> 'Modus',
	'SHOP_MODULE_iSecupayPaymentMode_0'								=> 'Demo',
	'SHOP_MODULE_iSecupayPaymentMode_1'								=> 'Live',
	'SHOP_MODULE_sSecupayPaymentApiKey'								=> 'API Key',
	'SHOP_MODULE_blSecupayPaymentDebug'								=> 'Debug ',
	'SHOP_MODULE_iSecupayPaymentExperience'							=> 'Teilen Sie uns Ihre Zahlungserfahrungen mit dem Kunden mit (setzt eine entsprechende Freischaltung für den jeweiliegen Vertrag voraus).',
	'SHOP_MODULE_iSecupayPaymentExperience_0'						=> 'Nein',
	'SHOP_MODULE_iSecupayPaymentExperience_1'						=> 'Ja',
	'SHOP_MODULE_iSecupayPaymentInvoiceTAutoSend'					=> 'Automatische übermittlung von Tracking Informationen.',
	'SHOP_MODULE_iSecupayPaymentInvoiceTAutoSend_0'					=> 'Nein',
	'SHOP_MODULE_iSecupayPaymentInvoiceTAutoSend_1'					=> 'Ja',
	'SHOP_MODULE_iSecupayPaymentTheme'								=> 'Schriftfarbe der Zahlungs-Icons',
	'SHOP_MODULE_iSecupayPaymentTheme_0'							=> 'dunkel',
	'SHOP_MODULE_iSecupayPaymentTheme_1'							=> 'hell',

	'SHOP_MODULE_GROUP_Settings_SecupayPayment_Debit'				=> 'Lastschrift',
	'SHOP_MODULE_blSecupayPaymentDebitActive'						=> 'Lastschrift aktiv',
	'SHOP_MODULE_sSecupayPaymentDebitShopname'						=> 'Shopname im Verwendungszweck (Bezeichnung aus der Titelleiste falls leer)',
	'SHOP_MODULE_iSecupayPaymentDebitDeliveryAddress'				=> 'Möchten Sie die Zahlungsart bei abweichender Lieferanschrift deaktivieren? Alternativ ist es möglich einen Hinweis anzuzeigen.',
	'SHOP_MODULE_iSecupayPaymentDebitDeliveryAddress_0'				=> 'Nein',
	'SHOP_MODULE_iSecupayPaymentDebitDeliveryAddress_1'				=> 'Ja',
	'SHOP_MODULE_iSecupayPaymentDebitDeliveryAddress_2'				=> 'Hinweis',

	'SHOP_MODULE_GROUP_Settings_SecupayPayment_CreditCard'			=> 'Kreditkarte',
	'SHOP_MODULE_blSecupayPaymentCreditCardActive'					=> 'Kreditkarte aktiv',
	'SHOP_MODULE_iSecupayPaymentCreditCardDeliveryAddress'			=> 'Möchten Sie die Zahlungsart bei abweichender Lieferanschrift deaktivieren? Alternativ ist es möglich einen Hinweis anzuzeigen.',
	'SHOP_MODULE_iSecupayPaymentCreditCardDeliveryAddress_0'		=> 'Nein',
	'SHOP_MODULE_iSecupayPaymentCreditCardDeliveryAddress_1'		=> 'Ja',
	'SHOP_MODULE_iSecupayPaymentCreditCardDeliveryAddress_2'		=> 'Hinweis',

	'SHOP_MODULE_GROUP_Settings_SecupayPayment_PrePay'				=> 'Vorkasse',
	'SHOP_MODULE_blSecupayPaymentPrePayActive'						=> 'Vorkasse aktiv',

	'SHOP_MODULE_GROUP_Settings_SecupayPayment_Invoice'				=> 'Rechnungskauf',
	'SHOP_MODULE_blSecupayPaymentInvoiceActive'						=> 'Rechnungskauf aktiv',
	'SHOP_MODULE_iSecupayPaymentInvoiceDeliveryAddress'				=> 'Möchten Sie die Zahlungsart bei abweichender Lieferanschrift deaktivieren? Alternativ ist es möglich einen Hinweis anzuzeigen.',
	'SHOP_MODULE_iSecupayPaymentInvoiceDeliveryAddress_0'			=> 'Nein',
	'SHOP_MODULE_iSecupayPaymentInvoiceDeliveryAddress_1'			=> 'Ja',
	'SHOP_MODULE_iSecupayPaymentInvoiceDeliveryAddress_2'			=> 'Hinweis',
	'SHOP_MODULE_iSecupayPaymentInvoiceAutoInvoice'					=> 'Rechnungmummer anstatt Bestellnummer übermitteln',
	'SHOP_MODULE_iSecupayPaymentInvoiceAutoInvoice_0'				=> 'Nein',
	'SHOP_MODULE_iSecupayPaymentInvoiceAutoInvoice_1'				=> 'Ja',
	'SHOP_MODULE_iSecupayPaymentInvoiceAutoSend'					=> 'Automatische Versandmeldung Rechnungskauf. WICHTIG! In der Datenbank muss die Tracking-ID hinterlegt werden.',
	'SHOP_MODULE_iSecupayPaymentInvoiceAutoSend_0'					=> 'Nein',
	'SHOP_MODULE_iSecupayPaymentInvoiceAutoSend_1'					=> 'Ja',

	'SHOP_MODULE_GROUP_Settings_SecupayPayment_Deactivate'			=> 'Modul deaktivieren',
	'SHOP_MODULE_blSecupayPaymentDeactivateClearSettings'			=> 'Beim deaktivieren alle Einstellungen des Moduls entfernen',
	'SHOP_MODULE_blSecupayPaymentDeactivateRemovePaymentMethods'	=> 'Beim deaktivieren alle secupay Zahlungsmethoden entfernen',

	'SECUPAY_AJAX_STATUS'											=> 'Der Status der Zahlung ist: ',
	'SECUPAY_AJAX_REFUND'											=> 'Die Status der Rückerstattung ist: ',
	'SECUPAY_AJAX_ERROR_REQUEST_FAILED'								=> 'Die Abfrage über secupay war nicht erfolgreich.',
	'SECUPAY_AJAX_ERROR_PARAMS_MISSING'								=> 'Es ist ein unerwarteter Fehler aufgetreten.',
];