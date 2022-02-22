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
[{if $oView->should_secupay_warn_delivery()}]
	[{$smarty.block.parent}]
	<p style="color:red">[{oxmultilang ident="SECUPAY_LIEFERADRESSE_TEXT"}]</p>
[{else}]
	[{$smarty.block.parent}]
[{/if}]