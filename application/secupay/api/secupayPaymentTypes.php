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

/*
 * 	mapping of secupay payment types to oxid payment ids
 *
 * 	@author feistel
 */

if (!class_exists("secupayPaymentTypes")) {
    /**
     * Class secupayPaymentTypes
     */
    class secupayPaymentTypes
    {
        /**
         * @var array
         */
        static $secupay_payment_types = Array(

            Array(
                'payment_id'                => 'secupay_creditcard',
                'payment_type'              => 'creditcard',
                'payment_option_name'       => 'secupay_creditcard_active',
                'payment_desc'              => 'Kreditkarte',
                'payment_desc1'             => 'Credit Card',
                'payment_shortdesc'         => 'Kreditkarte',
                'onAccepted_setOrderPaid'   => true,
                'can_check_delivery_adress' => true,
                'delivery_adress_option_name' => 'secupay_creditcard_delivery_adress'
            ),
            Array(
                'payment_id'                  => 'secupay_debit',
                'payment_type'                => 'debit',
                'payment_option_name'         => 'secupay_debit_active',
                'payment_desc'                => 'Lastschrift',
                'payment_desc1'               => 'Debit',
                'payment_shortdesc'           => 'Lastschrift',
                'onAccepted_setOrderPaid'     => true,
                'can_check_delivery_adress'   => true,
                'delivery_adress_option_name' => 'secupay_debit_delivery_adress'
            ),
            Array(
                'payment_id'                  => 'secupay_invoice',
                'payment_type'                => 'invoice',
                'payment_option_name'         => 'secupay_invoice_active',
                'payment_desc'                => 'Rechnungskauf',
                'payment_desc1'               => 'Invoice',
                'payment_shortdesc'           => 'Rechnungskauf',
                'onAccepted_setOrderPaid'     => true,
                'can_check_delivery_adress'   => true,
                'delivery_adress_option_name' => 'secupay_invoice_delivery_adress'
            ),
            Array(
                'payment_id'              => 'secupay_prepay',
                'payment_type'            => 'prepay',
                'payment_option_name'     => 'secupay_prepay_active',
                'payment_desc'            => 'Vorkasse',
                'payment_desc1'           => 'Prepay',
                'payment_shortdesc'       => 'Vorkasse',
                'onAccepted_setOrderPaid' => true
            ),
            Array(
                'payment_id'              => 'secupay_sofort',
                'payment_type'            => 'sofort',
                'payment_option_name'     => 'secupay_sofort_active',
                'payment_desc'            => 'Sofort Überweisung',
                'payment_desc1'           => 'Pay Now',
                'payment_shortdesc'       => 'Sofort Überweisung',
                'onAccepted_setOrderPaid' => true,
                'can_check_delivery_adress' => false
            ),
            /* Array(
              'payment_id' => 'secupay_paypal',
              'payment_type' => 'paypal',
              'payment_option_name' => 'secupay_paypal_active',
              'payment_desc' => 'secupay.Paypal',
              'payment_shortdesc' => 'Paypal',
              'onAccepted_setOrderPaid' => false
              ), */
        );

        /**
         * @param $payment_id
         *
         * @return bool|mixed
         */
        static function getSecupayPaymentType($payment_id)
        {

            foreach (self::$secupay_payment_types as $type) {
                if ($type['payment_id'] == $payment_id) {
                    return $type['payment_type'];
                }
            }

            return false;
        }

        /**
         * @param $payment_type
         *
         * @return bool|mixed
         */
        static function getOxidPaymentId($payment_type)
        {

            foreach (self::$secupay_payment_types as $type) {
                if ($type['payment_type'] == $payment_type) {
                    return $type['payment_id'];
                }
            }

            return false;
        }

        /**
         * @param $payment_id
         *
         * @return bool|mixed
         */
        static function getSecupayPaymentOptionName($payment_id)
        {

            foreach (self::$secupay_payment_types as $type) {
                if ($type['payment_id'] == $payment_id) {
                    return $type['payment_option_name'];
                }
            }
            return false;
        }

        /**
         * @param $payment_id
         *
         * @return bool|mixed
         */
        static function getSecupayPaymentDesc($payment_id)
        {
            foreach (self::$secupay_payment_types as $type) {
                if ($type['payment_id'] == $payment_id) {
                    return $type['payment_desc'];
                }
            }
            return false;
        }

        /**
         * @param $payment_id
         *
         * @return bool|mixed
         */
        static function getSecupayPaymentDesc1($payment_id)
        {
            foreach (self::$secupay_payment_types as $type) {
                if ($type['payment_id'] == $payment_id) {
                    return $type['payment_desc1'];
                }
            }
            return false;
        }

        /**
         * @param $payment_id
         *
         * @return bool|mixed
         */
        static function getSecupayPaymentShortDesc($payment_id)
        {
            foreach (self::$secupay_payment_types as $type) {
                if ($type['payment_id'] == $payment_id) {
                    return $type['payment_shortdesc'];
                }
            }
            return false;
        }

        /**
         * @param $payment_id
         *
         * @return bool|mixed
         */
        static function isOnAccepted_setOrderPaid($payment_id)
        {
            foreach (self::$secupay_payment_types as $type) {
                if ($type['payment_id'] == $payment_id) {
                    if (isset($type['onAccepted_setOrderPaid']) && !empty($type['onAccepted_setOrderPaid'])) {
                        return $type['onAccepted_setOrderPaid'];
                    } else {
                        return false;
                    }
                }
            }
            return false;
        }

        /**
         * @param $payment_id
         *
         * @return bool|mixed
         */
        static function getSecupayCheckDeliveryOptionName($payment_id)
        {
            foreach (self::$secupay_payment_types as $type) {
                if ($type['payment_id'] == $payment_id) {
                    if ($type['can_check_delivery_adress']) {
                        return $type['delivery_adress_option_name'];
                    } else {
                        return false;
                    }
                }
            }
            return false;
        }
    }
}