<dl>
	<dt>
		<input id="payment_[{$sPaymentID}]" type="radio" name="paymentid" value="[{$sPaymentID}]" [{if $oView->getCheckedPaymentId() == $paymentmethod->oxpayments__oxid->value}]checked[{/if}]>
		<label for="payment_[{$sPaymentID}]"><b>[{ $paymentmethod->oxpayments__oxdesc->value}] [{ if $paymentmethod->fAddPaymentSum }]([{ $paymentmethod->fAddPaymentSum }] [{ $currency->sign}])[{/if}]</b></label>
	</dt>
        <img src="https://www.secupay.ag/sites/default/files/media/Icons/secupay_creditcard.png">
	<dd class="[{if $oView->getCheckedPaymentId() == $paymentmethod->oxpayments__oxid->value}]activePayment[{/if}]">

[{foreach from=$paymentmethod->getDynValues() item=value name=PaymentDynValues}]
<tr onclick="oxid.form.select('paymentid',[{$inptcounter}]);">
	<td></td>
	<td><label>[{ $value->name}]</label></td>
	<td>
		<input id="test_Payment_[{$sPaymentID}]_[{$smarty.foreach.PaymentDynValues.iteration}]" type="text" class="payment_text" size="20" maxlength="64" name="dynvalue[[{$value->name}]]" value="[{ $value->value}]">
	</td>
</tr>
[{/foreach}]
<!-- secupay text -->
<tr>
	<td colspan="3">
		[{ if $oView->getDeliveryAdress(2) }]
		<br/><div style="color:red;">[{ oxmultilang ident="SECUPAY_LIEFERADRESSE_TEXT" }]</div>
		[{/if}]
	</td>
</tr>

[{if $paymentmethod->oxpayments__oxlongdesc->value}]
<div class="desc">
	[{$paymentmethod->oxpayments__oxlongdesc->getRawValue()}]
</div>
[{/if}]

	</dd>
</dl>
