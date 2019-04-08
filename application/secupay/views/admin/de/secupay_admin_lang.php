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
 */

$sLangName = "Deutsch";
// -------------------------------
// RESOURCE_IDENTITFIER => STRING
// -------------------------------
$aLang = array(
    'charset'                               => 'ISO-8859-15',
    'SECUPAY_DYN_MAIN_TITLE'                => 'secupay - einfach.sicher.zahlen',
    'SECUPAY_DYN_MAIN_VERSION'              => 'Version',
    'SECUPAY_DYN_MAIN_INFO'                 => 'Sie k&ouml;nnen die Einstellungen f&uuml;r secupay im Reiter "Einstellungen" unter "Erweiterungen" - "Module" - "secupay Zahlmodul" vornehmen.',
    'SECUPAY_DYN_MAIN_HOMEPAGE'             => 'secupay Webseite',
    'SECUPAY_DYN_MAIN_HOMEPAGE_URL'         => 'http://www.secupay.ag',
    'SECUPAY_DYN_MAIN_LOGINPAGE'            => 'secupay Account Login',
    'SECUPAY_DYN_MAIN_LOGINPAGE_URL'        => 'https://connect.secupay.ag',
    'SECUPAY_DYN_CONFIG_CURL_MISSING'       => 'Die CURL-Erweiterung fehlt!',
    'SECUPAY_DYN_LIST_MENUITEM'             => 'Stammdaten',
    'SECUPAY_DYN_LIST_MENUSUBITEM'          => 'secupay',
    'secupay_dyn_main'                      => 'Info',
    'secupay_dyn_splogin'                   => 'secupay Login',
    'SHOP_MODULE_secupay_debit_active'      => 'Lastschrift aktiv',
    'SHOP_MODULE_secupay_creditcard_active' => 'Kreditkarte aktiv',
    'SHOP_MODULE_secupay_prepay_active'     => 'Vorkasse aktiv',
    'SHOP_MODULE_secupay_sofort_active'     => 'Sofort Überweisung aktiv',
    /*'SHOP_MODULE_secupay_paypal_active' => 'Paypal aktiv',*/
    'SHOP_MODULE_secupay_invoice_active'    => 'Rechnungskauf aktiv',
    'SHOP_MODULE_secupay_blMode'            => 'Modus',
    'SHOP_MODULE_secupay_blMode_0'          => 'Demo',
    'SHOP_MODULE_secupay_blMode_1'          => 'Live',
    'SHOP_MODULE_secupay_blCession'         => "Form der Abtretungserkl&auml;rung",
    'SHOP_MODULE_secupay_blCession_0'       => 'Formal, "Sie"',
    'SHOP_MODULE_secupay_blCession_1'       => 'Pers&ouml;nlich, "Du"',
    'SHOP_MODULE_secupay_blLieferadresse'   => 'Warnhinweis aktivieren, dass Versand trotz abweichender Lieferadresse an die Rechnungsadresse erfolgt',
    'SHOP_MODULE_secupay_blPush'            => 'Bidirektionale Kommunikation mit secupay deaktivieren',
    'SHOP_MODULE_secupay_api_key'           => 'API Key',
    'SHOP_MODULE_secupay_shopname_ls'       => 'Shopname im Verwendungszweck (Bezeichnung aus der Titelleiste falls leer)',
    'SHOP_MODULE_GROUP_secupay_main'        => 'Allgemeines',
    'SHOP_MODULE_GROUP_secupay_creditcard'  => 'Kreditkarte',
    'SHOP_MODULE_GROUP_secupay_debit'       => 'Lastschrift',
    'SHOP_MODULE_GROUP_secupay_invoice'     => 'Rechnungskauf',
    'SHOP_MODULE_GROUP_secupay_prepay'      => 'Vorkasse',
    'SHOP_MODULE_GROUP_secupay_sofort'      => 'SOFORT Überweiung',
    'SHOP_MODULE_GROUP_secupay_paypal'      => 'Paypal',

    'SHOP_MODULE_secupay_creditcard_delivery_adress'     => 'M&ouml;chten Sie die Zahlungsart bei abweichender Lieferanschrift deaktivieren? Alternativ ist es m&ouml;glich einen Hinweis anzuzeigen.',
    'SHOP_MODULE_secupay_creditcard_delivery_adress_0'   => 'Nein',
    'SHOP_MODULE_secupay_creditcard_delivery_adress_1'   => 'Ja',
    'SHOP_MODULE_secupay_creditcard_delivery_adress_2'   => 'Hinweis',
    'SHOP_MODULE_secupay_debit_delivery_adress'     => 'M&ouml;chten Sie die Zahlungsart bei abweichender Lieferanschrift deaktivieren? Alternativ ist es m&ouml;glich einen Hinweis anzuzeigen.',
    'SHOP_MODULE_secupay_debit_delivery_adress_0'   => 'Nein',
    'SHOP_MODULE_secupay_debit_delivery_adress_1'   => 'Ja',
    'SHOP_MODULE_secupay_debit_delivery_adress_2'   => 'Hinweis',
    'SHOP_MODULE_secupay_invoice_delivery_adress'   => 'M&ouml;chten Sie die Zahlungsart bei abweichender Lieferanschrift deaktivieren? Alternativ ist es m&ouml;glich einen Hinweis anzuzeigen.',
    'SHOP_MODULE_secupay_invoice_delivery_adress_0' => 'Nein',
    'SHOP_MODULE_secupay_invoice_delivery_adress_1' => 'Ja',
    'SHOP_MODULE_secupay_invoice_delivery_adress_2' => 'Hinweis',
    'ORDER_OVERVIEW_SECUPAY_HASH'                   => 'secupay HASH',
    'SHOP_MODULE_secupay_blDebug_log'               => 'Debug ',
    'secupay'                                       => 'secupay ',
    'SHOP_MODULE_secupay_invoice_autosend_0'        => 'Nein',
    'SHOP_MODULE_secupay_invoice_autosend_1'        => 'Ja',
    'SHOP_MODULE_secupay_invoice_autoinvoice_0'     => 'Nein',
    'SHOP_MODULE_secupay_invoice_autoinvoice_1'     => 'Ja',
    'SHOP_MODULE_secupay_experience_0'              => 'Nein',
    'SHOP_MODULE_secupay_experience_1'              => 'Ja',
    'SHOP_MODULE_secupay_invoice_tautosend_0'       => 'Nein',
    'SHOP_MODULE_secupay_invoice_tautosend_1'       => 'Ja',
    'SHOP_MODULE_secupay_invoice_autoinvoice'       => 'Rechnungmummer anstatt Bestellnummer &uuml;bermitteln',
    'SHOP_MODULE_secupay_invoice_autosend'          => 'Automatische Versandmeldung Rechnungskauf. WICHTIG! In der Datenbank muss die Tracking-ID hinterlegt werden.',
    'SHOP_MODULE_secupay_experience'                => 'Teilen Sie uns Ihre Zahlungserfahrungen mit dem Kunden mit (setzt eine entsprechende Freischaltung f&uuml;r den jeweiliegen Vertrag voraus).',
    'SHOP_MODULE_secupay_invoice_tautosend'         => 'Automatische &uuml;bermittlung Tracking Informationen.'
);

/*
  [{ oxmultilang ident="GENERAL_YOUWANTTODELETE" }]
 */
