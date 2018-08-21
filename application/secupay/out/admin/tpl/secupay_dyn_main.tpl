[{include file="headitem.tpl" title="[secupay]"}]
[{ if $readonly }]
[{assign var="readonly" value="readonly disabled"}]
[{else}]
[{assign var="readonly" value=""}]
[{/if}]

<form name="transfer" id="transfer" action="[{ $oViewConf->getSelfLink() }]" method="post">
    [{ $oViewConf->getHiddenSid() }]
    <input type="hidden" name="oxid" value="[{ $oxid }]"/>
    <input type="hidden" name="cl" value="secupay_dyn_main"/>
</form>

<div style="float:right;">
    <p><a href="[{ oxmultilang ident="SECUPAY_DYN_MAIN_HOMEPAGE_URL" }]" target="_blank"><img src="https://www.secupay.ag/sites/default/files/media/Icons/secupay_logo.png" alt="secupay" border="0"/></a></p>
    <p>
	<a href="[{ oxmultilang ident="SECUPAY_DYN_MAIN_HOMEPAGE_URL" }]" target="_blank">[{ oxmultilang ident="SECUPAY_DYN_MAIN_HOMEPAGE" }]</a>
	<br />
	<a href="[{ oxmultilang ident="SECUPAY_DYN_MAIN_LOGINPAGE_URL" }]" target="_blank">[{ oxmultilang ident="SECUPAY_DYN_MAIN_LOGINPAGE" }]</a>
	<p>[{ oxmultilang ident="SECUPAY_DYN_MAIN_VERSION" }]: [{$oView->getSecupayVersion()}]</p>
    </p>
</div>
<div style="height: 100%;">

    <h1>[{ oxmultilang ident="SECUPAY_DYN_MAIN_TITLE" }]</h1>
    <p>
	[{ oxmultilang ident="SECUPAY_DYN_MAIN_INFO" }]	
    </p>
    <p>
    <div style="text-align: center;display:block;height: 80%;width:100%;">
        <iframe style="border:0px; display:block; height: 80%;width:100%;"  name="secupay_kk" scrolling="auto" frameborder="0" marginheight="0px" marginwidth="0px" src="https://connect.secupay.ag/shopiframe.php?api_key=[{ $oView->getApiKey() }]&shop=oxid&shopversion=[{ $oView->getShopVersion() }]&modulversion=[{ $oView->getModVersion() }]"></iframe>
    </div>
    </p>
</div>

[{include file="bottomnaviitem.tpl" }]
[{include file="bottomitem.tpl"}]
