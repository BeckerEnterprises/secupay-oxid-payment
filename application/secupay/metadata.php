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

/**
 * secupay AG Oxid Payment module metadata
 *
 * @version 2.2.0
 * @package Secupay
 */

/**
 * Metadata version
 */
$sMetadataVersion = '1.2';

/**
 * Module information
 */
$aModule = array(
    'id'          => 'secupay',
    'title'       => 'secupay Zahlmodul',
    'version'     => '2.3.0',
    'author'      => 'secupay AG',
    'url'         => 'http://www.secupay.com',
    'email'       => 'info@secupay.com',
    'thumbnail'   => 'img/secupay_logo.png',
    'description' => array(
        'de' => 'secupay einfach.sicher.zahlen',
        'en' => 'secupay safe online payments',
    ),
    'extend'      => array(
        'order'            => 'secupay/controllers/secupayOrder',
        'oxpaymentgateway' => 'secupay/models/secupayPaymentGateway',
        'payment'          => 'secupay/controllers/secupayPayment',
        'order_list'       => 'secupay/controllers/admin/secupayautosend',
    ),
    'files'       => array(
        'secupay_push'           => 'secupay/controllers/secupay_push.php',
        'secupay_dyn'            => 'secupay/controllers/admin/secupay_dyn.php',
        'secupay_dyn_list'       => 'secupay/controllers/admin/secupay_dyn_list.php',
        'secupay_dyn_main'       => 'secupay/controllers/admin/secupay_dyn_main.php',
        'secupay_dyn_splogin'    => 'secupay/controllers/admin/secupay_dyn_splogin.php',
        'secupay_refund_void'    => 'secupay/controllers/admin/secupay_refund_void.php',
        'secupay_status_request' => 'secupay/controllers/admin/secupay_status_request.php',
        'secupay_events'         => 'secupay/core/secupay_events.php',
    ),
    'blocks'      => array(
        array(
            'template' => 'page/checkout/payment.tpl',
            'block'    => 'select_payment',
            'file'     => 'out/blocks/page/checkout/payment/select_payment.tpl'
        ),
        array(
            'template' => 'page/checkout/order.tpl',
            'block'    => 'shippingAndPayment',
            'file'     => 'out/blocks/page/checkout/order/shippingAndPayment.tpl'
        )
    ),
    'settings'    => array(
        array(
            'group'       => 'secupay_main',
            'name'        => 'secupay_blMode',
            'type'        => 'select',
            'value'       => '0',
            'constraints' => '0|1'
        ),
        array('group' => 'secupay_main', 'name' => 'secupay_api_key', 'type' => 'str', 'value' => '<%SPAPIKEY%>'),
        array('group' => 'secupay_main', 'name' => 'secupay_blDebug_log', 'type' => 'bool', 'value' => 'true'),
        array(
            'group'       => 'secupay_main',
            'name'        => 'secupay_experience',
            'type'        => 'select',
            'value'       => '1',
            'constraints' => '0|1'
        ),
        array(
            'group' => 'secupay_creditcard',
            'name'  => 'secupay_creditcard_active',
            'type'  => 'bool',
            'value' => 'true'
        ),
        array('group' => 'secupay_debit', 'name' => 'secupay_debit_active', 'type' => 'bool', 'value' => 'true'),
        array('group' => 'secupay_invoice', 'name' => 'secupay_invoice_active', 'type' => 'bool', 'value' => 'true'),
        array('group' => 'secupay_prepay', 'name' => 'secupay_prepay_active', 'type' => 'bool', 'value' => 'true'),
        array('group' => 'secupay_sofort', 'name' => 'secupay_sofort_active', 'type' => 'bool', 'value' => 'true'),
        //array('group' => 'secupay_paypal', 'name' => 'secupay_paypal_active', 'type' => 'bool', 'value' => 'true'),
        array('group' => 'secupay_debit', 'name' => 'secupay_shopname_ls', 'type' => 'str', 'value' => ''),
        array(
            'group'       => 'secupay_creditcard',
            'name'        => 'secupay_creditcard_delivery_adress',
            'type'        => 'select',
            'value'       => '0',
            'constraints' => '0|1|2'
        ),
        array(
            'group'       => 'secupay_debit',
            'name'        => 'secupay_debit_delivery_adress',
            'type'        => 'select',
            'value'       => '0',
            'constraints' => '0|1|2'
        ),
        array(
            'group'       => 'secupay_invoice',
            'name'        => 'secupay_invoice_delivery_adress',
            'type'        => 'select',
            'value'       => '0',
            'constraints' => '0|1|2'
        ),
        array(
            'group'       => 'secupay_invoice',
            'name'        => 'secupay_invoice_autoinvoice',
            'type'        => 'select',
            'value'       => '1',
            'constraints' => '0|1'
        ),
        array(
            'group'       => 'secupay_invoice',
            'name'        => 'secupay_invoice_autosend',
            'type'        => 'select',
            'value'       => '1',
            'constraints' => '0|1'
        ),
        array(
            'group'       => 'secupay_main',
            'name'        => 'secupay_invoice_tautosend',
            'type'        => 'select',
            'value'       => '1',
            'constraints' => '0|1'
        )
    ),
    'templates'   => array(
        'secupay_dyn.tpl'                => 'secupay/out/admin/tpl/secupay_dyn.tpl',
        'secupay_dyn_list.tpl'           => 'secupay/out/admin/tpl/secupay_dyn_list.tpl',
        'secupay_dyn_main.tpl'           => 'secupay/out/admin/tpl/secupay_dyn_main.tpl',
        'secupay_dyn_splogin.tpl'        => 'secupay/out/admin/tpl/secupay_dyn_splogin.tpl',
        'payment_secupay_iframe.tpl'     => 'secupay/out/tpl/payment_secupay_iframe.tpl',
        'payment_secupay_creditcard.tpl' => 'secupay/out/tpl/payment_secupay_creditcard.tpl',
        'payment_secupay_debit.tpl'      => 'secupay/out/tpl/payment_secupay_debit.tpl',
        'payment_secupay_invoice.tpl'    => 'secupay/out/tpl/payment_secupay_invoice.tpl',
        'payment_secupay_prepay.tpl'     => 'secupay/out/tpl/payment_secupay_prepay.tpl',
        'payment_secupay_sofort.tpl'     => 'secupay/out/tpl/payment_secupay_sofort.tpl',
        'payment_secupay_paypal.tpl'     => 'secupay/out/tpl/payment_secupay_paypal.tpl',

    ),
    'events'      => array(
        'onActivate'   => 'secupay_events::onActivate',
        'onDeactivate' => 'secupay_events::onDeactivate'
    ),
);
