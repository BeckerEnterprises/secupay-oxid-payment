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
    'charset'                              => 'UTF-8',
    'SECUPAY_INVOICE'                      => 'Secupay Rechnung',
    // configuration for settings
    'SHOP_MODULE_GROUP_GROUP_MAIN'         => 'Allgemeines',
    'SHOP_MODULE_SECUPAY_BANKNAME'         => 'Bank',
    'SHOP_MODULE_SECUPAY_BANKCODE'         => 'BLZ',
    'SHOP_MODULE_SECUPAY_ACCOUNTNUMBER'    => 'Kontonummer f&uuml;r Secupay Rechnungen',
    'SHOP_MODULE_SECUPAY_IBAN'             => 'IBAN',
    'SHOP_MODULE_SECUPAY_BIC'              => 'BIC',
    'SECUPAY_ADDRESS_INVOICE'              => 'Der Rechnungsbetrag wurde an die [recipient_legal], abgetreten.',
    'SECUPAY_PAYMENT_HEADER'               => 'Eine Zahlung mit schuldbefreiender Wirkung ist nur auf folgendes Konto möglich:',
    'SECUPAY_RECIPIENT'                    => 'Empfänger: ',
    'SECUPAY_ACCOUNT_NUMBER'               => 'Kontonummer: ',
    'SECUPAY_BANK_CODE'                    => 'BLZ: ',
    'SECUPAY_BANK_NAME'                    => 'Bank: ',
    'SECUPAY_IBAN'                         => 'IBAN: ',
    'SECUPAY_BIC'                          => 'BIC: ',
    'SECUPAY_PURPOSE'                      => 'Verwendungszweck: ',
    'SECUPAY_INFO_BOTTOM_1'                => "Um diese Rechnung bequem online zu zahlen, können Sie den QR-Code mit einem internetfähigen Telefon",
    'SECUPAY_INFO_BOTTOM_2'                => "einscannen oder Sie nutzen diese URL:",
    'SHOP_MODULE_SECUPAY_DAYS_TO_BILLDATE' => "Zahlungsfrist in Tagen",
    'SHOP_MODULE_SECUPAY_USE_UTF8'         => "Shop verwendet UTF-8",
    'SECUPAY_PREPAY_BILLDATE'              => "Die Lieferung erfolgt erst nach Zahlungseingang",
);

/*
  [{ oxmultilang ident="GENERAL_YOUWANTTODELETE" }]
 */
