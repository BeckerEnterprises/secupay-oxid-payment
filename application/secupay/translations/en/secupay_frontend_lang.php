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
// RESOURCE_IDENTIFIER => STRING
// -------------------------------
$aLang = array(
    'charset'                            => 'UTF-8',
    'SECUPAY_LIEFERADRESSE_TEXT'         => "Warning: The shipment will be send exclusively to the specified billing address.",
    'SECUPAY_CESSION_TEXT_PREPAY_FORMAL' => "Dear Customer, <br/> Please note that we work together to process our payments with secupay AG. <br/> Please transfer the payment amount to the following bank account: <br/>",
    'SECUPAY_CESSION_TEXT_PREPAY'        => "We are working to process our payments with the secupay AG <br/> Please transfer the payment amount to the following bank account:. <br/>",
    'SECUPAY_ACCOUNTOWNER'               => "Account owner: ",
    'SECUPAY_ACCOUNTNUMBER'              => "Account number: ",
    'SECUPAY_BANKCODE'                   => "BankCode: ",
    'SECUPAY_PURPOSE'                    => "Purpose: ",
    'SECUPAY_PLEASE_MAKE_PAYMENT'        => "Please make the payment now<br/> ",
    'SECUPAY_PAYMENT_CANCEL_MESSAGE'     => "Transaction canceled",
    'SECUPAY_PAYMENT_ERROR_MESSAGE'      => "Transaction failed",
    'SECUPAY_ORDER_ERROR_MESSAGE'        => "Order failed - please check the basket",
    'SECUPAY_LANG'                       => "en_us",
);

/*
[{ oxmultilang ident="GENERAL_YOUWANTTODELETE" }]
*/
