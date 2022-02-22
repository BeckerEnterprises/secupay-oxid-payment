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
[{capture append="oxidBlock_content"}]
	[{block name="checkout_order_errors"}]
		[{if $oView->isConfirmAGBActive() && $oView->isConfirmAGBError() == 1}]
			[{include file="message/error.tpl" statusMessage="READ_AND_CONFIRM_TERMS"|oxmultilangassign}]
		[{/if}]
		[{assign var="iError" value=$oView->getAddressError()}]
		[{if $iError == 1}]
			[{include file="message/error.tpl" statusMessage="ERROR_DELIVERY_ADDRESS_WAS_CHANGED_DURING_CHECKOUT"|oxmultilangassign}]
		[{/if}]
	[{/block}]

	[{* ordering steps *}]
	[{include file="page/checkout/inc/steps.tpl" active=4}]

	[{block name="checkout_order_main"}]
		[{if !$oView->showOrderButtonOnTop()}]
			<div class="alert alert-info">[{oxmultilang ident="SECUPAY_PLEASE_MAKE_PAYMENT"}]</div>
		[{/if}]

		[{block name="checkout_order_details"}]
			<div style="text-align: center;">
				<iframe src="[{$oView->getSecupayIframeUrl()}]" style="border:none; height:660px; width:100%; max-width:560px;" name="secupay_iframe" id="payment_frame">
					<p>[{oxmultilang ident="SECUPAY_IFRAMES_NOT_SUPPORTED"}]<a href="[{$oView->getSecupayIframeUrl()}]">Secupay</a></p>
				</iframe>
			</div>
    	[{/block}]
    [{/block}]
    [{insert name="oxid_tracker" title=$template_title}]
[{/capture}]

[{assign var="template_title" value="REVIEW_YOUR_ORDER"|oxmultilangassign}]
[{include file="layout/page.tpl" title=$template_title location=$template_title}]