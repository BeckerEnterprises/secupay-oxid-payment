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
	use \Secupay\Payment\Core\SecupayApi;

	use \OxidEsales\Facts\Facts;

	use \OxidEsales\Eshop\Core\ShopVersion;
	use \OxidEsales\Eshop\Application\Controller\Admin\AdminDetailsController;

	use \OxidEsales\EshopCommunity\Internal\Container\ContainerFactory;
	use \OxidEsales\EshopCommunity\Internal\Framework\Module\Configuration\Bridge\ModuleConfigurationDaoBridgeInterface;

	class SecupayMain extends AdminDetailsController
	{
		protected $_sThisTemplate = 'secupay/admin/secupay_main.tpl';

		public function render()
		{
			parent::render();

			return $this->_sThisTemplate;
		}

		public function getSecupayVersion()
		{
			return $this->getModVersion();
		}

		public function getApiKey()
		{
			return $this->getConfig()->getConfigParam('sSecupayPaymentApiKey');
		}

		public function getShopVersion()
		{
			return 'OXID '.oxNew(Facts::class)->getEdition().' '.ShopVersion::getVersion();
		}

		public function getModVersion()
		{
			return ContainerFactory::getInstance()->getContainer()->get(ModuleConfigurationDaoBridgeInterface::class)->get('Secupay_Payment')->getVersion();
		}
	}
}