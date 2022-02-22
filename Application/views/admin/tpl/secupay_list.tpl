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
[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign box="list"}]
[{assign var="where" value=$oView->getListFilter()}]

[{if $readonly}]
    [{assign var="readonly" value="readonly disabled"}]
[{else}]
    [{assign var="readonly" value=""}]
[{/if}]

<script>
	function forceReloadingListFrame()
	{
		top.basefrm.list.document.reloadFrame = true;
	}

	function reloadListFrame()
	{
		if(top.basefrm.list && top.basefrm.list.document.reloadFrame)
			top.oxid.admin.updateList();
	}

	function ChangeEditBar(sLocation, sPos)
	{
		var oSearch = document.getElementById("search");
		oSearch.actedit.value = sPos;
		oSearch.submit();

		var oTransfer = parent.edit.document.getElementById("transfer");
		oTransfer.cl.value = sLocation;

		top.forceReloadingEditFrame();
	}

	window.SP = {
		'set_sp_ta_id': function(ta_id)
		{
			document.getElementById("transaction_id").value = ta_id;
		},
		'credit_advice': function(hash, amount_ta, partial)
		{
			var sp_amount = partial ? prompt("Betrag der Gutschrift in Euro",""+amount_ta) : amount_ta;

			if(isNaN(sp_amount) || sp_amount < 0)
			{
				alert("Betrag nicht gueltig.");
				return false;
			}

			if(sp_amount === null)
				return false;

			var request = new XMLHttpRequest();
			request.open('GET', "[{$sRefundURL}]", true);
			request.setRequestHeader('Content-Type', "application/x-www-form-urlencoded; charset=UTF-8");
			request.send('apikey=' + encodeURIComponent("[{$sApiKey}]") + '&amount=' + encodeURIComponent(sp_amount) + '&hash=' + encodeURIComponent(hash));
			request.onreadystatechange = function()
			{
				if((request.readyState == XMLHttpRequest.DONE) && (request.status === 0 || ((request.status >= 200) && (request.status < 400))))
				{
					if((data = JSON.parse(request.responseText)) && data.status)
						alert(data.status);

					forceReloadingListFrame();
					reloadListFrame();
				}
			};
		},
		'status_request': function(hash)
		{
			var request = new XMLHttpRequest();
			request.open('GET', "[{$sStatusURL}]", true);
			request.setRequestHeader('Content-Type', "application/x-www-form-urlencoded; charset=UTF-8");
			request.send('apikey=' + encodeURIComponent("[{$sApiKey}]") + '&hash=' + encodeURIComponent(hash));
			request.onreadystatechange = function()
			{
				if((request.readyState == XMLHttpRequest.DONE) && (request.status === 0 || ((request.status >= 200) && (request.status < 400))))
				{
					forceReloadingListFrame();
					reloadListFrame();
				}
			};
		}
	};

	window.onload = function()
	{
		top.reloadEditFrame();
		[{if $updatelist == 1}]top.oxid.admin.updateList('[{$oxid}]');[{/if}]
	}
</script>
<style>
	.sp_capture span.ico
	{
		background: url("../out/admin/src/bg/ico_link_arrow.gif") no-repeat scroll 0 1px transparent;
		float: left;
		height: 15px;
		line-height: 1px;
		margin: 0 3px 0 1px;
		width: 15px;
	}
</style>

<form name="search" id="search" action="[{ $oViewConf->getSelfLink() }]" method="post">
	[{include file="_formparams.tpl" cl="clSecupayList" lstrt=$lstrt actedit=$actedit oxid=$oxid fnc="" language=$actlang editlanguage=$actlang}]
</form>

<div id="liste" style="overflow:auto; height:60%;">
	<table width="70%" style="float:left;margin-bottom: 20px;overflow:scroll;">
		<tr>
			<td class = "listheader">HASH</td>
			<td class = "listheader">TACode</td>
			<td class = "listheader">Bestellnr.</td>
			<td class = "listheader">Zahlungsart</td>
			<td class = "listheader">Betrag</td>
			<td class = "listheader">Erstellt</td>
			<td class = "listheader">Status</td>
			<td class = "listheader">Nachricht</td>
			<td class = "listheader" style="width:45px;">Aktionen</td>
		</tr>
		[{foreach from=$aTaList item=aListItem name="secupay_transaction_loop"}]
			[{if $smarty.foreach.secupay_transaction_loop.iteration is even}]
				[{assign var="listclass" value="listitem"}]
			[{else}]
				[{assign var="listclass" value="listitem2"}]
			[{/if}]
			<tr id="row.[{$aListItem.hash}]">
				<td valign="top" class="[{$listclass}]"><div class="listitemfloating">[{$aListItem.hash}]</div></td>
				<td valign="top" class="[{$listclass}]"><div class="listitemfloating">[{$aListItem.transaction_id}]</div></td>
				<td valign="top" class="[{$listclass}]"><div class="listitemfloating">[{$aListItem.oxordernr}]</div></td>
				<td valign="top" class="[{$listclass}]"><div class="listitemfloating">[{$aListItem.payment_method}]</div></td>
				<td valign="top" class="[{$listclass}]"><div class="listitemfloating" style="text-align:right;">[{$aListItem.amount}] &euro;</div></td>
				<td valign="top" class="[{$listclass}]"><div class="listitemfloating">[{$aListItem.created}]</div></td>
				<td valign="top" class="[{$listclass}]"><div class="listitemfloating">[{$aListItem.status}]</div></td>
				<td valign="top" class="[{$listclass}]"><div class="listitemfloating">[{$aListItem.msg}]</div></td>
				<td valign="top" class="[{$listclass}]"><div class="listitemfloating">[{$aListItem.actions}]</div></td>	    
			</tr>
		[{/foreach}]
	</table>
</div>

[{include file="pagetabsnippet.tpl" noOXIDCheck="true"}]

<script>
if(parent.parent)
{
	parent.parent.sShopTitle = '[{$actshopobj->oxshops__oxname->getRawValue()|oxaddslashes}]';
	parent.parent.sMenuItem = '[{oxmultilang ident="SECUPAY_LIST_MENUITEM"}]';
	parent.parent.sMenuSubItem = '[{oxmultilang ident="SECUPAY_LIST_MENUSUBITEM"}]';
	parent.parent.sWorkArea = '[{$_act}]';
	parent.parent.setTitle();
}
</script>

[{include file="bottomitem.tpl"}]