<?php declare(strict_types=1);
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

namespace Secupay\Payment\Application\Model
{
	class PaymentType
	{
		private $sId = null;
		private $sType = null;
		private $sOptionName = null;
		private $sDescription = null;
		private $sShortDescription = null;
		private $blOnAcceptedSetOrderPaid = false;
		private $blCanCheckDeliveryAdress = false;
		private $sDeliveryAdressOptionName = null;

		public function getId() : ?string { return $this->sId; }
		public function setId(string $sId = null) { $this->sId = $sId; }

		public function getType() : ?string { return $this->sType; }
		public function setType(string $sType = null) { $this->sType = $sType; }

		public function getOptionName() : ?string { return $this->sOptionName; }
		public function setOptionName(string $sOptionName = null) { $this->sOptionName = $sOptionName; }

		public function getDescription() : ?string { return $this->sDescription; }
		public function setDescription(string $sDescription = null) { $this->sDescription = $sDescription; }

		public function getShortDescription() : ?string { return $this->sShortDescription; }
		public function setShortDescription(string $sShortDescription = null) { $this->sShortDescription = $sShortDescription; }

		public function isOnAcceptedSetOrderPaid() : bool { return $this->blOnAcceptedSetOrderPaid; }
		public function setOnAcceptedSetOrderPaid(bool $blOnAcceptedSetOrderPaid) { $this->blOnAcceptedSetOrderPaid = $blOnAcceptedSetOrderPaid; }

		public function isCanCheckDeliveryAdress() : bool { return $this->blCanCheckDeliveryAdress; }
		public function setCanCheckDeliveryAdress(bool $blCanCheckDeliveryAdress) { $this->blCanCheckDeliveryAdress = $blCanCheckDeliveryAdress; }

		public function getDeliveryAdressOptionName() : ?string { return $this->sDeliveryAdressOptionName; }
		public function setDeliveryAdressOptionName(string $sDeliveryAdressOptionName = null) { $this->sDeliveryAdressOptionName = $sDeliveryAdressOptionName; }
	}
}