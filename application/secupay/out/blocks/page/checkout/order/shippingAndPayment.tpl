[{if $oView->should_secupay_warn_delivery()}]
    [{$smarty.block.parent}]
    <p style="color:red">
        [{oxmultilang ident="SECUPAY_LIEFERADRESSE_TEXT"}]
    </p>
    [{else}]
    [{$smarty.block.parent}]
    [{/if}]