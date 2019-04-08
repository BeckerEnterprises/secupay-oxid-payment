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
 * secupay AG Oxid Secupay invoice module metadata
 *
 * @version 1.4.4
 * @package Secupay
 */

/**
 * Metadata version
 */
$sMetadataVersion = '1.1';

/**
 * Module information
 */
$aModule = array(
    'id'          => 'secupay_invoice',
    'title'       => 'secupay Invoice PDF',
    'thumbnail'   => 'img/secupay_logo.png',
    'version'     => '1.4.4',
    'author'      => 'secupay AG',
    'url'         => 'http://www.secupay.ag',
    'email'       => 'info@secupay.ag',
    'description' => array(
        'de' => 'Module zur Erstellung der Rechnungs-PDF für secupay.Rechnungskauf und secupay Vorkasse.',
        'en' => 'Module for making secupay invoice and Prepay PDF files.',
    ),
    'extend'      => array(
        'oxorder'        => 'secupay_invoice/secupay_invoice_oxorder',
        'order_overview' => 'secupay_invoice/secupay_order_overview',
    ),
    'blocks'      => array(
        array(
            'template' => 'order_overview.tpl',
            'block'    => 'admin_order_overview_export',
            'file'     => 'admin_order_overview_export.tpl'
        ),
    ),
    'settings'    => array(
        array('group' => 'GROUP_MAIN', 'name' => 'SECUPAY_DAYS_TO_BILLDATE', 'type' => 'str', 'value' => '10'),
        array('group' => 'GROUP_MAIN', 'name' => 'SECUPAY_USE_UTF8', 'type' => 'bool', 'value' => 'true'),
    ),

);
