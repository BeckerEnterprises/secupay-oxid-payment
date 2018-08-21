[{include file="headitem.tpl" title="[secupay]"}]
[{ if $readonly }]
[{assign var="readonly" value="readonly disabled"}]
[{else}]
[{assign var="readonly" value=""}]
[{/if}]

<form name="transfer" id="transfer" action="[{ $oViewConf->getSelfLink() }]" method="post">
    [{ $oViewConf->getHiddenSid() }]
    <input type="hidden" name="oxid" value="[{ $oxid }]"/>
    <input type="hidden" name="cl" value="secupay_dyn_splogin"/>
</form>
<script type="text/javascript">
    
//<!--
//
//-->
</script>
<div style="text-align: center;display:block;height: 100%;width:100%;">
    <iframe style="border:0px; display:block; height: 100%;width:100%;" id="sp_iframe_id" name="sp_iframe" scrolling="auto" frameborder="0" marginheight="0px" marginwidth="0px" src="https://connect.secupay.ag/secupay.php"/>
</div>
[{include file="bottomnaviitem.tpl" }]
[{include file="bottomitem.tpl"}]