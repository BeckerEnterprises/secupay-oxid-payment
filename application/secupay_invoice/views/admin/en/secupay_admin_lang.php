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

$sLangName = "English";
// -------------------------------
// RESOURCE_IDENTITFIER => STRING
// -------------------------------
$aLang = array(
    'charset'                              => 'UTF-8',
    'SECUPAY_INVOICE'                      => 'Secupay Invoice',
    // configuration for settings
    'SHOP_MODULE_GROUP_GROUP_MAIN'         => 'General',
    'SHOP_MODULE_SECUPAY_BANKNAME'         => 'Bank',
    'SHOP_MODULE_SECUPAY_BANKCODE'         => 'Bank code',
    'SHOP_MODULE_SECUPAY_ACCOUNTNUMBER'    => 'Account number for Secupay invoices',
    'SHOP_MODULE_SECUPAY_IBAN'             => 'IBAN',
    'SHOP_MODULE_SECUPAY_BIC'              => 'BIC',
    'SECUPAY_ADDRESS_INVOICE'              => 'The invoice amount was assigned to [recipient_legal].',
    'SECUPAY_PAYMENT_HEADER'               => 'Payments with debt-discharging effect may only be made to the following account:',
    'SECUPAY_RECIPIENT'                    => 'Recipient: ',
    'SECUPAY_ACCOUNT_NUMBER'               => 'Account number: ',
    'SECUPAY_BANK_CODE'                    => 'Bank code: ',
    'SECUPAY_BANK_NAME'                    => 'Bank: ',
    'SECUPAY_IBAN'                         => 'IBAN: ',
    'SECUPAY_BIC'                          => 'BIC: ',
    'SECUPAY_PURPOSE'                      => 'Purpose: ',
    'SECUPAY_INFO_BOTTOM_1'                => "Alternatively you can pay comfortably electronically via the provided QR code",
    'SECUPAY_INFO_BOTTOM_2'                => "or the following link:",
    'SHOP_MODULE_SECUPAY_DAYS_TO_BILLDATE' => "Days until term of payment",
    'SHOP_MODULE_SECUPAY_USE_UTF8'         => "Shop uses UTF-8",
    'SECUPAY_PREPAY_BILLDATE'              => "Delivery only after receipt of Payment",
);

/** The translation usage in template files:
 * [{ oxmultilang ident="GENERAL_YOUWANTTODELETE" }]
 */
