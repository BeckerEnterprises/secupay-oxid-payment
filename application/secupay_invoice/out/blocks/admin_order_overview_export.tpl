
                  [{ if $edit && $oView->canSecupayExport() }]
                  <br>
                  <table cellspacing="0" cellpadding="0" style="padding-top: 5px; padding-left: 5px; padding-right: 5px; padding-bottom: 5px; border : 1px #A9A9A9; border-style : solid solid solid solid;" width="220">
                  <form name="myedit2" id="myedit2" action="[{ $oViewConf->getSelfLink() }]" method="post" target="expPDF">
                  [{ $oViewConf->getHiddenSid() }]
                  <input type="hidden" name="cl" value="order_overview">
                  <input type="hidden" name="fnc" value="createSecupayPDF">
                  <input type="hidden" name="oxid" value="[{ $oxid }]">
                  <tr>
                    <td colspan="2">[{ oxmultilang ident="SECUPAY_INVOICE" }]</td>
                  </tr>
                  <tr>
                    <td rowspan="2">
                      <img src="[{$oViewConf->getImageUrl()}]/pdf_icon.gif" width="41" height="38" alt="" border="0" hspace="0" vspace="0" align="absmiddle">
                    </td>
                    <td align="right" class="edittext">
                      [{ oxmultilang ident="GENERAL_LANGUAGE" }]<select name="pdflanguage" class="saveinnewlanginput" style="width:80px;">
                      [{foreach from=$alangs key=lang item=slang}]
                      <option value="[{ $lang }]"[{ if $lang == "0" }]SELECTED[{/if}]>[{ $slang }]</option>
                      [{/foreach}]
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td align="right" class="edittext"><br />
                      <input type="submit" class="edittext" name="save" value="[{ oxmultilang ident="ORDER_OVERVIEW_PDF" }]">
                      <iframe name="expPDF" width="0" height="0" border="0" style="display:none;"></iframe>
                    </td>
                  </tr>
                  </table>
                  </form>
                  [{/if}]
                  <br>
                  [{$smarty.block.parent}]
