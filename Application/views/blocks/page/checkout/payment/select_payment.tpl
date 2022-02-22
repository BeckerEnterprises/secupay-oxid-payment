[{*
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
  *}]
[{if $oView->isSecupayPaymentType($sPaymentID) and $oView->isSecupayPaymentTypeActive($sPaymentID)}]
	<dl>
		<dt>
			<input id="payment_[{$sPaymentID}]" type="radio" name="paymentid" value="[{$sPaymentID}]" [{if $oView->getCheckedPaymentId() == $paymentmethod->oxpayments__oxid->value}]checked[{/if}]>
			<label for="payment_[{$sPaymentID}]"><b>[{$paymentmethod->oxpayments__oxdesc->value}]</b></label>
			<img src='https://www.secupay.ag/sites/default/files/media/Icons/[{oxmultilang ident="SECUPAY_LANG"}]/[{$sPaymentID}].png'>
		</dt>
		<dd class="payment-option[{if $oView->getCheckedPaymentId() == $paymentmethod->oxpayments__oxid->value}] activePayment[{/if}]">
			[{if $paymentmethod->getPrice()}]
				[{assign var="oPaymentPrice" value=$paymentmethod->getPrice() }]
				[{if $oViewConf->isFunctionalityEnabled('blShowVATForPayCharge') }]
					[{strip}]([{oxprice price=$oPaymentPrice->getNettoPrice() currency=$currency}][{if $oPaymentPrice->getVatValue() > 0}][{oxmultilang ident="PLUS_VAT"}] [{oxprice price=$oPaymentPrice->getVatValue() currency=$currency}][{/if}])[{/strip}]
				[{else}]
					([{oxprice price=$oPaymentPrice->getBruttoPrice() currency=$currency}])
				[{/if}]
			[{/if}]
			[{foreach from=$paymentmethod->getDynValues() item=value name=PaymentDynValues}]
				<div class="form-group">
					<label class="control-label col-lg-3" for="[{$sPaymentID}]_[{$smarty.foreach.PaymentDynValues.iteration}]">[{$value->name}]</label>
					<div class="col-lg-9">
						<input id="[{$sPaymentID}]_[{$smarty.foreach.PaymentDynValues.iteration}]" type="text" class="form-control textbox" size="20" maxlength="64" name="dynvalue[[{$value->name}]]" value="[{$value->value}]">
					</div>
				</div>
			[{/foreach}]
			[{if $oView->getDeliveryAdress(2)}]<div style="color:red;">[{ oxmultilang ident="SECUPAY_LIEFERADRESSE_TEXT" }]</div>[{/if}]
			<div class="clearfix"></div>

			[{block name="checkout_payment_longdesc"}]
				[{if $paymentmethod->oxpayments__oxlongdesc->value|strip_tags|trim}]
					<div class="desc">[{$paymentmethod->oxpayments__oxlongdesc->getRawValue()}]</div>
				[{/if}]
			[{/block}]
		</dd>
	</dl>
[{else}]
	[{$smarty.block.parent}]
[{/if}]