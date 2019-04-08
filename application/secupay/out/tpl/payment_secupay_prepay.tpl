<dl>
    <dt>
        <input id="payment_[{$sPaymentID}]" type="radio" name="paymentid" value="[{$sPaymentID}]" [{if $oView->getCheckedPaymentId() == $paymentmethod->oxpayments__oxid->value}]checked[{/if}]>
        <label for="payment_[{$sPaymentID}]"><b>[{$paymentmethod->oxpayments__oxdesc->value}] [{if $paymentmethod->fAddPaymentSum }]([{$paymentmethod->fAddPaymentSum}] [{ $currency->sign}])[{/if}]</b></label>
    </dt>
    <img src="https://www.secupay.com/sites/default/files/media/Icons/[{oxmultilang ident=SECUPAY_LANG}]/secupay_prepay.png">
    <dd class="[{if $oView->getCheckedPaymentId() == $paymentmethod->oxpayments__oxid->value}]activePayment[{/if}]">
        [{if $paymentmethod->oxpayments__oxlongdesc->value}]
        <div class="desc">
            [{ $paymentmethod->oxpayments__oxlongdesc->value}]
        </div>
        [{/if}]
    </dd>
</dl>
