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
	<input type="hidden" name="cl" value="clSecupayLogin">
	<input type="hidden" name="editlanguage" value="[{$editlanguage}]">
</form>
<div style="text-align:center;display:block;height:100%;width:100%;">
	<iframe style="border:0px;display:block;height:100%;width:100%;" id="sp_iframe_id" name="sp_iframe" scrolling="auto" frameborder="0" marginheight="0px" marginwidth="0px" src="https://connect.secupay.ag/secupay.php"></iframe>
</div>

[{include file="bottomnaviitem.tpl"}]
[{include file="bottomitem.tpl"}]