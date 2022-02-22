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
[{include file="headitem.tpl" title="CONTENT_MAIN_TITLE"|oxmultilangassign}]

[{if $readonly}]
	[{assign var="readonly" value="readonly disabled"}]
[{else}]
	[{assign var="readonly" value=""}]
[{/if}]

<form name="transfer" id="transfer" action="[{$oViewConf->getSelfLink()}]" method="post">
	[{$oViewConf->getHiddenSid()}]
	<input type="hidden" name="oxid" value="[{$oxid}]">
	<input type="hidden" name="cl" value="clSecupayMain">
	<input type="hidden" name="editlanguage" value="[{$editlanguage}]">
</form>
<div style="float:right;">
	<p>
		<a href='[{oxmultilang ident="SECUPAY_MAIN_HOMEPAGE_URL"}]' target="_blank"><img src='[{$oViewConf->getModuleUrl("Secupay/Payment", "out/pictures/secupay-logo.png")}]' alt="secupay" border="0"></a>
	</p>
	<p>
		<a href='[{oxmultilang ident="SECUPAY_MAIN_HOMEPAGE_URL"}]' target="_blank">[{oxmultilang ident="SECUPAY_MAIN_HOMEPAGE"}]</a><br>
		<a href='[{oxmultilang ident="SECUPAY_MAIN_LOGINPAGE_URL"}]' target="_blank">[{oxmultilang ident="SECUPAY_MAIN_LOGINPAGE"}]</a><br>
		[{oxmultilang ident="SECUPAY_MAIN_VERSION"}]: [{$oView->getSecupayVersion()}]
	</p>
</div>
<div style="height: 100%;">
	<h1>[{oxmultilang ident="SECUPAY_MAIN_TITLE"}]</h1>
	<p>[{oxmultilang ident="SECUPAY_MAIN_INFO"}]</p>
	<div style="text-align:center;display:block;height:80%;width:100%;">
		<iframe style="border:0px;display:block;height:80%;width:100%;" name="secupay_kk" scrolling="auto" frameborder="0" marginheight="0px" marginwidth="0px" src="https://connect.secupay.ag/shopiframe.php?api_key=[{$oView->getApiKey()}]&shop=oxid&shopversion=[{$oView->getShopVersion()}]&modulversion=[{$oView->getModVersion()}]"></iframe>
	</div>
</div>

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]