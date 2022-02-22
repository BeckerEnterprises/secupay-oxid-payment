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

namespace Secupay\Payment\Application\Controller\Admin
{
	use \OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;

	class SecupayLogin extends AdminDetailsController
	{
		protected $_sThisTemplate = 'secupay/admin/secupay_login.tpl';
	}
}