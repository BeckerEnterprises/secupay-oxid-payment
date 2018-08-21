[{include file="headitem.tpl" title="secupay"|oxmultilangassign box="list"}]


<script type="text/javascript">
<!--

function forceReloadingListFrame( oxId )
{
    //forcing list frame to reload after submit
    top.basefrm.list.document.reloadFrame = true;
}

function reloadListFrame()
{
  if (top.basefrm.list) {
      if (top.basefrm.list.document.reloadFrame) {
          top.oxid.admin.updateList();
      }
  }
}

function ChangeEditBar( sLocation, sPos){
    var oSearch = document.getElementById("search");
    oSearch.actedit.value=sPos;
    oSearch.submit();    

    var oTransfer = parent.edit.document.getElementById("transfer");
    oTransfer.cl.value=sLocation;

    //forcing edit frame to reload after submit
    top.forceReloadingEditFrame();
}

window.onLoad = top.reloadEditFrame();

(function(){
    var sp = {
    set_sp_ta_id : function(ta_id){
        document.getElementById("transaction_id").value=ta_id;
    },
    credit_advice: function(hash,amount_ta,partial){
        var sp_amount=0;
        if(partial === true){
        sp_amount = prompt("Betrag der Gutschrift in Euro",""+amount_ta);
        }else{
        sp_amount = amount_ta;
        }

        if(isNaN(sp_amount) || sp_amount < 0 ){
        alert("Betrag nicht gueltig.");
            return false;
        }
        if(sp_amount === null) {
            return false;
        }
        $.ajax({
        dataType:"text",
        data:({apikey:"[{ $spapikey }]",amount : sp_amount, hash : hash}),
        url:"[{$oView->getPath()}]secupay_refund_void.php",
        success : function(data){
            data = $.parseJSON(data);
            if(data){
            if(data.status){
                alert(data.status);
            }
            }
            forceReloadingListFrame();
            reloadListFrame();
        }
        });
    },
    status_request: function(hash){
        $.ajax({
        dataType:"text",
        data:({apikey:"[{ $spapikey }]",hash : hash}),
        //url:"secupay_status_request.php",
		url:"[{$oView->getPath()}]secupay_status_request.php",
        success : function(data){
            forceReloadingListFrame();
            reloadListFrame();
        }
        });
    }
    }
    window.SP=sp;
})(); 

//-->
</script>
<style type="text/css">
    .sp_capture span.ico {
        background: url("../out/admin/src/bg/ico_link_arrow.gif") no-repeat scroll 0 1px transparent;
        float: left;
        height: 15px;
        line-height: 1px;
        margin: 0 3px 0 1px;
        width: 15px;
    }
</style>
<form name="search" id="search" action="[{ $oViewConf->getSelfLink() }]" method="post">
    [{ $oViewConf->getHiddenSid() }]
    <input type="hidden" name="actedit" value="[{ $actedit }]" />
    <input type="hidden" name="cl" value="secupay_dyn_list"/>
    <input type="hidden" name="oxid" value="x" />
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
	[{assign var="blWhite" value=""}]
	[{assign var="_cnt" value=0}]
	[{foreach from=$splist item=listitem}]
	[{assign var="_cnt" value=$_cnt+1}]
	<tr id="row.[{ $listitem.hash }]">
	    [{assign var="listclass" value=listitem$blWhite }]
	    <td valign="top" class="[{ $listclass}]"><div class="listitemfloating">[{ $listitem.hash }]</div></td>
            <td valign="top" class="[{ $listclass}]"><div class="listitemfloating">[{ $listitem.transaction_id }]</div></td>
	    <td valign="top" class="[{ $listclass}]"><div class="listitemfloating">[{ $listitem.oxordernr }]</div></td>
	    <td valign="top" class="[{ $listclass}]"><div class="listitemfloating">[{ $listitem.payment_method}]</div></td>
	    <td valign="top" class="[{ $listclass}]"><div style="text-align: right;" class="listitemfloating">[{ $listitem.amount}]&euro;</div></td>
	    <td valign="top" class="[{ $listclass}]"><div class="listitemfloating">[{ $listitem.created }]</div></td>
		<td valign="top" class="[{ $listclass}]"><div class="listitemfloating">[{ $listitem.status }]</div></td>
	    <td valign="top" class="[{ $listclass}]"><div class="listitemfloating">[{ $listitem.msg }]</div></td>
	    <td valign="top" class="[{ $listclass}]"><div class="listitemfloating">[{ $listitem.actions }]</div></td>	    
	</tr>
	[{if $blWhite == "2"}]
	[{assign var="blWhite" value=""}]
	[{else}]
	[{assign var="blWhite" value="2"}]
	[{/if}]
	[{/foreach}]

    </table>
</div>

<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
<script>!window.jQuery && document.write('<script src="js/jquery-1.4.2.min.js"><\/script>')</script>

<script type="text/javascript">
</script>

[{assign var="blWhite" value=""}]
[{assign var="_cnt" value=0}]
[{foreach from=$mylist item=listitem}]
[{assign var="_cnt" value=$_cnt+1}]
<tr id="row.[{$_cnt}]">

    [{ if $listitem->blacklist == 1}]
    [{assign var="listclass" value=listitem3 }]
    [{ else}]
    [{assign var="listclass" value=listitem$blWhite }]
    [{ /if}]
    [{ if $listitem->oxvoucherseries__oxid->value == $oxid }]
    [{assign var="listclass" value=listitem4 }]
    [{ /if}]
    <td valign="top" class="[{ $listclass}]" height="15"><div class="listitemfloating">&nbsp;<a href="Javascript:top.oxid.admin.editThis('[{ $listitem->oxvoucherseries__oxid->value}]');" class="[{ $listclass}]">[{ if !$listitem->oxvoucherseries__oxserienr->value }]-[{ oxmultilang ident="GENERAL_NONAME" }]-[{else}][{ $listitem->oxvoucherseries__oxserienr->value }][{/if}]</a></div></td>
    <td valign="top" class="[{ $listclass}]"><div class="listitemfloating"><a href="Javascript:top.oxid.admin.editThis('[{ $listitem->oxvoucherseries__oxid->value }]');" class="[{$listclass}]">[{ $listitem->oxvoucherseries__oxdiscount->value }][{if $listitem->oxvoucherseries__oxdiscounttype->value == "percent"}] %[{/if}]</a></div></td>
    <td valign="top" class="[{ $listclass}]"><div class="listitemfloating"><a href="Javascript:top.oxid.admin.editThis('[{ $listitem->oxvoucherseries__oxid->value }]');" class="[{$listclass}]">[{ $listitem->oxvoucherseries__oxbegindate->value }]</a></div></td>
    <td valign="top" class="[{ $listclass}]"><div class="listitemfloating"><a href="Javascript:top.oxid.admin.editThis('[{ $listitem->oxvoucherseries__oxid->value }]');" class="[{$listclass}]">[{ $listitem->oxvoucherseries__oxenddate->value }]</a></div></td>
    <td valign="top" class="[{ $listclass}]"><div class="listitemfloating"><a href="Javascript:top.oxid.admin.editThis('[{ $listitem->oxvoucherseries__oxid->value }]');" class="[{$listclass}]">[{ $listitem->oxvoucherseries__oxminimumvalue->value }]</a></div></td>
    <td class="[{ $listclass}]">
	[{if !$readonly}]
	<a href="Javascript:top.oxid.admin.deleteThis('[{ $listitem->oxvoucherseries__oxid->value }]');" class="delete" id="del.[{$_cnt}]" title="" [{include file="help.tpl" helpid=item_delete}]></a>
	[{/if}]
    </td>
</tr>
[{if $blWhite == "2"}]
[{assign var="blWhite" value=""}]
[{else}]
[{assign var="blWhite" value="2"}]
[{/if}]
[{/foreach}]

[{include file="pagetabsnippet.tpl" noOXIDCheck="true"}]

<script type="text/javascript">
if (parent.parent) 
{   parent.parent.sShopTitle   = "[{$actshopobj->oxshops__oxname->value}]";
    parent.parent.sMenuItem    = "[{ oxmultilang ident='SECUPAY_DYN_LIST_MENUITEM' }]";
    parent.parent.sMenuSubItem = "[{ oxmultilang ident='SECUPAY_DYN_LIST_MENUSUBITEM' }]";
    parent.parent.sWorkArea    = "[{$_act}]";
    parent.parent.setTitle();
}
</script>

[{include file="bottomitem.tpl"}]
