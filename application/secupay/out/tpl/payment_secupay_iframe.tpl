[{capture append="oxidBlock_content"}]

    [{block name="checkout_order_errors"}]
        [{ if $oView->isConfirmAGBActive() && $oView->isConfirmAGBError() == 1 }]
            [{include file="message/error.tpl" statusMessage="PAGE_CHECKOUT_ORDER_READANDCONFIRMTERMS"|oxmultilangassign }]
        [{/if}]
        [{assign var="iError" value=$oView->getAddressError() }]
        [{ if $iError == 1}]
           [{include file="message/error.tpl" statusMessage="ERROR_DELIVERY_ADDRESS_WAS_CHANGED_DURING_CHECKOUT"|oxmultilangassign }]
        [{ /if}]
    [{/block}]

    [{* ordering steps *}]
    [{include file="page/checkout/inc/steps.tpl" active=4 }]

    [{block name="checkout_order_main"}]
        [{if !$oView->showOrderButtonOnTop()}]
            <div class="lineBox clear">
                <span>&nbsp;</span>
                <span class="title" style="float:left">[{ oxmultilang ident="SECUPAY_PLEASE_MAKE_PAYMENT" }]</span>
            </div>
        [{/if}]
<div style="text-align: center;">
    <iframe src="[{ $oView->getIframeUrl()}]" style="border:none; height:660px; width:100%; max-width:560px;" name="secupay_iframe" id="payment_frame">
        <p>
            Ihr Browser kann leider keine eingebetteten Frames anzeigen:
            Sie k&ouml;nnen die Seite &uuml;ber den folgenden Verweis
            aufrufen: <a href="[{ $oView->getIframeUrl() }]">Secupay</a>
        </p>
    </iframe>
</div>
    [{/block}]
    [{insert name="oxid_tracker" title=$template_title }]
[{/capture}]

[{assign var="template_title" value="PAGE_CHECKOUT_ORDER_TITLE"|oxmultilangassign}]
[{include file="layout/page.tpl" title=$template_title location=$template_title}]