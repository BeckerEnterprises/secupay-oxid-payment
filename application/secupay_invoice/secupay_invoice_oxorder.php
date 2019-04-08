<?php
/**
 * secupay Payment Module
 * @author    secupay AG
 * @copyright 2019, secupay AG
 * @license   LICENSE.txt
 * @category  Payment
 *
 * Description:
 *  Oxid Plugin for integration of secupay AG payment services
 */

use OxidEsales\Eshop\Core\Exception\DatabaseConnectionException;
use OxidEsales\Eshop\Core\Exception\DatabaseErrorException;

require_once getShopBasePath() . 'modules/secupay_invoice/api/secupay_invoice_data.php';

/**
 * PDF renderer helper class, used to store data, sizes etc..
 */
class SP_PdfBlock
{
    /**
     * array of data to render
     * @var array
     */
    protected $_aCache = array();

    /**
     * string default Font
     * @var array
     */
    protected $_sFont = 'Arial';
    /**
     * Runs and evaluates cached code
     *
     * @param object $oPdf object which methods will be executed
     *
     */
    public function run($oPdf)
    {
        foreach ($this->_aCache as $oItem) {
            $sFn = $oItem->sFunc;
            switch (count($oItem->aParams)) {
                case 0:
                    $oPdf->$sFn();
                    break;
                case 1:
                    $oPdf->$sFn($oItem->aParams[0]);
                    break;
                case 2:
                    $oPdf->$sFn($oItem->aParams[0], $oItem->aParams[1]);
                    break;
                case 3:
                    $oPdf->$sFn($oItem->aParams[0], $oItem->aParams[1], $oItem->aParams[2]);
                    break;
                case 4:
                    $oPdf->$sFn($oItem->aParams[0], $oItem->aParams[1], $oItem->aParams[2], $oItem->aParams[3]);
                    break;
            }
        }
    }
    /**
     * Caches Line call with parameters
     *
     * @param int $iLPos    left position
     * @param int $iLHeight left height
     * @param int $iRPos    right position
     * @param int $iRHeight right height
     *
     */
    public function line($iLPos, $iLHeight, $iRPos, $iRHeight)
    {
        $this->_toCache('Line', array($iLPos, $iLHeight, $iRPos, $iRHeight));
    }
    /**
     * Stores cacheable parameters
     *
     * @param string $sFunc   cacheable function name
     * @param array  $aParams cacheable parameters
     *
     */
    protected function _toCache($sFunc, $aParams)
    {
        $oItem           = new stdClass();
        $oItem->sFunc    = $sFunc;
        $oItem->aParams  = $aParams;
        $this->_aCache[] = $oItem;
    }
    /**
     * Caches Text call with parameters
     *
     * @param int    $iLPos    left position
     * @param int    $iLHeight height
     * @param string $sString  string to write
     *
     */
    public function text($iLPos, $iLHeight, $sString)
    {
        $this->_toCache('Text', array($iLPos, $iLHeight, $sString));
    }

    /**
     * Caches SetFont call with parameters
     *
     * @param string $sType   font type (Arial, Tahoma ...)
     * @param string $sWeight font weight ('', 'B', 'U' ...)
     * @param string $sSize   font size ('10', '8', '6' ...)
     *
     */
    public function font($sType, $sWeight, $sSize)
    {
        $this->_toCache('SetFont', array($sType, $sWeight, $sSize));
    }

    /**
     * Adjusts height after new page addition
     *
     * @param int $iDelta new height
     *
     */
    public function ajustHeight($iDelta)
    {
        foreach ($this->_aCache as $key => $oItem) {
            switch ($oItem->sFunc) {
                case 'Line':
                    $this->_aCache[$key]->aParams[3] += $iDelta;
                    $this->_aCache[$key]->aParams[1] += $iDelta;
                    break;
                case 'Text':
                    $this->_aCache[$key]->aParams[1] += $iDelta;
                    break;
            }
        }
    }

    /**
     * Caches SetFont call with parameters
     *
     * @return string
     */
    public function getFont()
    {
        return $this->_sFont;
    }
}

/**
 * Order summary class
 */
class SP_PdfArticleSummary extends SP_PdfBlock
{
    /**
     * order object
     * @var object
     */
    protected $_oData = null;

    /**
     * pdf object
     * @var object
     */
    protected $_oPdf = null;

    /**
     * Constructor
     *
     * @param object $oData order object
     * @param object $oPdf  pdf object
     *
     */
    public function __construct($oData, $oPdf)
    {
        $this->_oData = $oData;
        $this->_oPdf  = $oPdf;
    }
    /**
     * Generates order info block (prices, VATs, etc )
     *
     * @param int $iStartPos text start position
     *
     * @return int
     */
    public function generate($iStartPos)
    {
        $this->font($this->getFont(), '', 10);
        $siteH = $iStartPos;

        // #1147 discount for vat must be displayed
        if (!$this->_oData->oxorder__oxdiscount->value) {
            $this->_setTotalCostsWithoutDiscount($siteH);
        } else {
            $this->_setTotalCostsWithDiscount($siteH);
        }

        $siteH += 12;

        // voucher info
        $this->_setVoucherInfo($siteH);

        // additional line separator
        if ($this->_oData->oxorder__oxdiscount->value || $this->_oData->oxorder__oxvoucherdiscount->value) {
            $this->line(45, $siteH - 3, 195, $siteH - 3);
        }

        // delivery info
        $this->_setDeliveryInfo($siteH);

        // payment info
        $this->_setPaymentInfo($siteH);

        // wrapping info
        $this->_setWrappingInfo($siteH);

        // TS protection info
        $this->_setTsProtection($siteH);

        // separating line
        $this->line(15, $siteH, 195, $siteH);
        $siteH += 4;

        // total order sum
        $this->_setGrandTotalPriceInfo($siteH);

        // separating line
        $this->line(15, $siteH, 195, $siteH);
        $siteH += 4;

        // payment method
        $this->_setPaymentMethodInfo($siteH);

        return $siteH - $iStartPos;
    }
    /**
     * Sets total costs values using order without discount
     *
     * @param int &$iStartPos text start position
     *
     */
    protected function _setTotalCostsWithoutDiscount(&$iStartPos)
    {
        $oLang = oxRegistry::getLang();

        // products netto price
        $this->line(15, $iStartPos + 1, 195, $iStartPos + 1);
        $sNetSum = $oLang->formatCurrency($this->_oData->oxorder__oxtotalnetsum->value, $this->_oData->getCurrency())
            . ' ' . $this->_oData->getCurrency()->name;
        $this->text(45, $iStartPos + 4, $this->_oData->translate('ORDER_OVERVIEW_PDF_ALLPRICENETTO'));
        $this->text(195 - $this->_oPdf->getStringWidth($sNetSum), $iStartPos + 4, $sNetSum);

        // #345 - product VAT info
        $iCtr = 0;
        foreach ($this->_oData->getVats() as $iVat => $dVatPrice) {
            $iStartPos += 4 * $iCtr;
            $sVATSum   = $oLang->formatCurrency($dVatPrice, $this->_oData->getCurrency()) . ' '
                . $this->_oData->getCurrency()->name;
            $this->text(
                45,
                $iStartPos + 8,
                $this->_oData->translate('ORDER_OVERVIEW_PDF_ZZGLVAT') . $iVat . $this->_oData->translate(
                    'ORDER_OVERVIEW_PDF_PERCENTSUM'
                )
            );
            $this->text(195 - $this->_oPdf->getStringWidth($sVATSum), $iStartPos + 8, $sVATSum);
            $iCtr++;
        }

        // products brutto price
        $sBrutPrice = $oLang->formatCurrency(
                $this->_oData->oxorder__oxtotalbrutsum->value,
                $this->_oData->getCurrency()
            ) . ' ' . $this->_oData->getCurrency()->name;
        $this->text(45, $iStartPos + 12, $this->_oData->translate('ORDER_OVERVIEW_PDF_ALLPRICEBRUTTO'));
        $this->text(195 - $this->_oPdf->getStringWidth($sBrutPrice), $iStartPos + 12, $sBrutPrice);
        $iStartPos++;

        // line separator
        $this->line(45, $iStartPos + 13, 195, $iStartPos + 13);
        $iStartPos += 5;
    }
    /**
     * Sets total costs values using order with discount
     *
     * @param int &$iStartPos text start position
     *
     */
    protected function _setTotalCostsWithDiscount(&$iStartPos)
    {
        $oLang = oxRegistry::getLang();

        // line separator
        $this->line(15, $iStartPos + 1, 195, $iStartPos + 1);

        if ($this->_oData->isNettoMode()) {
            // products netto price
            $sNetSum = $oLang->formatCurrency(
                    $this->_oData->oxorder__oxtotalnetsum->value,
                    $this->_oData->getCurrency()
                ) . ' ' . $this->_oData->getCurrency()->name;
            $this->text(45, $iStartPos + 4, $this->_oData->translate('ORDER_OVERVIEW_PDF_ALLPRICENETTO'));
            $this->text(195 - $this->_oPdf->getStringWidth($sNetSum), $iStartPos + 4, $sNetSum);

            // discount
            $dDiscountVal = $this->_oData->oxorder__oxdiscount->value;
            if ($dDiscountVal > 0) {
                $dDiscountVal *= -1;
            }
            $sDiscount = $oLang->formatCurrency($dDiscountVal, $this->_oData->getCurrency()) . ' '
                . $this->_oData->getCurrency()->name;
            $this->text(45, $iStartPos + 8, $this->_oData->translate('ORDER_OVERVIEW_PDF_DISCOUNT'));
            $this->text(195 - $this->_oPdf->getStringWidth($sDiscount), $iStartPos + 8, $sDiscount);
            $iStartPos++;

            // line separator
            $this->line(45, $iStartPos + 8, 195, $iStartPos + 8);

            $iCtr = 0;
            foreach ($this->_oData->getVats() as $iVat => $dVatPrice) {
                $iStartPos += 4 * $iCtr;
                $sVATSum   = $oLang->formatCurrency($dVatPrice, $this->_oData->getCurrency()) . ' '
                    . $this->_oData->getCurrency()->name;
                $this->text(
                    45,
                    $iStartPos + 12,
                    $this->_oData->translate('ORDER_OVERVIEW_PDF_ZZGLVAT') . $iVat . $this->_oData->translate(
                        'ORDER_OVERVIEW_PDF_PERCENTSUM'
                    )
                );
                $this->text(195 - $this->_oPdf->getStringWidth($sVATSum), $iStartPos + 12, $sVATSum);
                $iCtr++;
            }
            $iStartPos += 4;

            // products brutto price
            $sBrutPrice = $oLang->formatCurrency(
                    $this->_oData->oxorder__oxtotalbrutsum->value,
                    $this->_oData->getCurrency()
                ) . ' ' . $this->_oData->getCurrency()->name;
            $this->text(45, $iStartPos + 12, $this->_oData->translate('ORDER_OVERVIEW_PDF_ALLPRICEBRUTTO'));
            $this->text(195 - $this->_oPdf->getStringWidth($sBrutPrice), $iStartPos + 12, $sBrutPrice);
            $iStartPos += 4;
        } else {
            // products brutto price
            $sBrutPrice = $oLang->formatCurrency(
                    $this->_oData->oxorder__oxtotalbrutsum->value,
                    $this->_oData->getCurrency()
                ) . ' ' . $this->_oData->getCurrency()->name;
            $this->text(45, $iStartPos + 4, $this->_oData->translate('ORDER_OVERVIEW_PDF_ALLPRICEBRUTTO'));
            $this->text(195 - $this->_oPdf->getStringWidth($sBrutPrice), $iStartPos + 4, $sBrutPrice);

            // line separator
            $this->line(45, $iStartPos + 5, 195, $iStartPos + 5);

            // discount
            $dDiscountVal = $this->_oData->oxorder__oxdiscount->value;
            if ($dDiscountVal > 0) {
                $dDiscountVal *= -1;
            }
            $sDiscount = $oLang->formatCurrency($dDiscountVal, $this->_oData->getCurrency()) . ' '
                . $this->_oData->getCurrency()->name;
            $this->text(45, $iStartPos + 8, $this->_oData->translate('ORDER_OVERVIEW_PDF_DISCOUNT'));
            $this->text(195 - $this->_oPdf->getStringWidth($sDiscount), $iStartPos + 8, $sDiscount);
            $iStartPos++;

            // line separator
            $this->line(45, $iStartPos + 8, 195, $iStartPos + 8);
            $iStartPos += 4;

            // products netto price
            $sNetSum = $oLang->formatCurrency(
                    $this->_oData->oxorder__oxtotalnetsum->value,
                    $this->_oData->getCurrency()
                ) . ' ' . $this->_oData->getCurrency()->name;
            $this->text(45, $iStartPos + 8, $this->_oData->translate('ORDER_OVERVIEW_PDF_ALLPRICENETTO'));
            $this->text(195 - $this->_oPdf->getStringWidth($sNetSum), $iStartPos + 8, $sNetSum);

            // #345 - product VAT info
            $iCtr = 0;
            foreach ($this->_oData->getVats() as $iVat => $dVatPrice) {
                $iStartPos += 4 * $iCtr;
                $sVATSum   = $oLang->formatCurrency($dVatPrice, $this->_oData->getCurrency()) . ' '
                    . $this->_oData->getCurrency()->name;
                $this->text(
                    45,
                    $iStartPos + 12,
                    $this->_oData->translate('ORDER_OVERVIEW_PDF_ZZGLVAT') . $iVat . $this->_oData->translate(
                        'ORDER_OVERVIEW_PDF_PERCENTSUM'
                    )
                );
                $this->text(195 - $this->_oPdf->getStringWidth($sVATSum), $iStartPos + 12, $sVATSum);
                $iCtr++;
            }
            $iStartPos += 4;
        }
    }
    /**
     * Sets voucher values to pdf
     *
     * @param int &$iStartPos text start position
     *
     */
    protected function _setVoucherInfo(&$iStartPos)
    {
        $dVoucher = 0;
        if ($this->_oData->oxorder__oxvoucherdiscount->value) {
            $dDiscountVal = $this->_oData->oxorder__oxvoucherdiscount->value;
            if ($dDiscountVal > 0) {
                $dDiscountVal *= -1;
            }
            $sPayCost = oxRegistry::getLang()
                                  ->formatCurrency($dDiscountVal, $this->_oData->getCurrency()) . ' '
                . $this->_oData->getCurrency()->name;
            $this->text(45, $iStartPos, $this->_oData->translate('ORDER_OVERVIEW_PDF_VOUCHER'));
            $this->text(195 - $this->_oPdf->getStringWidth($sPayCost), $iStartPos, $sPayCost);
            $iStartPos += 4;
        }

        $iStartPos++;
    }
    /**
     * Sets delivery info to pdf
     *
     * @param int &$iStartPos text start position
     *
     */
    protected function _setDeliveryInfo(&$iStartPos)
    {
        $sAddString = '';
        $oLang      = oxRegistry::getLang();
        $oConfig    = oxRegistry::getConfig();

        if ($oConfig->getConfigParam('blShowVATForDelivery')) {
            // delivery netto
            $sDelCostNetto = $oLang->formatCurrency(
                    $this->_oData->getOrderDeliveryPrice()
                                 ->getNettoPrice(),
                    $this->_oData->getCurrency()
                ) . ' ' . $this->_oData->getCurrency()->name;
            $this->text(
                45,
                $iStartPos,
                $this->_oData->translate('ORDER_OVERVIEW_PDF_SHIPCOST') . ' ' . $this->_oData->translate(
                    'ORDER_OVERVIEW_PDF_NETTO'
                )
            );
            $this->text(195 - $this->_oPdf->getStringWidth($sDelCostNetto), $iStartPos, $sDelCostNetto);
            $iStartPos += 4;

            if ($oConfig->getConfigParam('sAdditionalServVATCalcMethod') != 'proportional') {
                $sVatValueText = $this->_oData->translate('ORDER_OVERVIEW_PDF_ZZGLVAT')
                    . $this->_oData->oxorder__oxdelvat->value . $this->_oData->translate(
                        'ORDER_OVERVIEW_PDF_PERCENTSUM'
                    );
            } else {
                $sVatValueText = $this->_oData->translate('TOTAL_PLUS_PROPORTIONAL_VAT');
            }

            // delivery VAT
            $sDelCostVAT = $oLang->formatCurrency(
                    $this->_oData->getOrderDeliveryPrice()
                                 ->getVATValue(),
                    $this->_oData->getCurrency()
                ) . ' ' . $this->_oData->getCurrency()->name;
            $this->text(45, $iStartPos, $sVatValueText);
            $this->text(195 - $this->_oPdf->getStringWidth($sDelCostVAT), $iStartPos, $sDelCostVAT);
            //$iStartPos += 4;

            $sAddString = ' ' . $this->_oData->translate('ORDER_OVERVIEW_PDF_BRUTTO');
        } else {
            // if canceled order, reset value
            if ($this->_oData->oxorder__oxstorno->value) {
                $this->_oData->oxorder__oxdelcost->setValue(0);
            }

            $sDelCost = $oLang->formatCurrency($this->_oData->oxorder__oxdelcost->value, $this->_oData->getCurrency())
                . ' ' . $this->_oData->getCurrency()->name;
            $this->text(45, $iStartPos, $this->_oData->translate('ORDER_OVERVIEW_PDF_SHIPCOST') . $sAddString);
            $this->text(195 - $this->_oPdf->getStringWidth($sDelCost), $iStartPos, $sDelCost);
        }
    }
    /**
     * Sets payment info to pdf
     *
     * @param int &$iStartPos text start position
     *
     */
    protected function _setPaymentInfo(&$iStartPos)
    {
        $oLang   = oxRegistry::getLang();
        $oConfig = oxRegistry::getConfig();

        if ($this->_oData->oxorder__oxstorno->value) {
            $this->_oData->oxorder__oxpaycost->setValue(0);
        }

        if ($oConfig->getConfigParam('blShowVATForDelivery')) {
            if ($this->_oData->oxorder__oxpayvat->value) {
                // payment netto
                $iStartPos     += 4;
                $sPayCostNetto = $oLang->formatCurrency(
                        $this->_oData->getOrderPaymentPrice()
                                     ->getNettoPrice(),
                        $this->_oData->getCurrency()
                    ) . ' ' . $this->_oData->getCurrency()->name;
                $this->text(
                    45,
                    $iStartPos,
                    $this->_oData->translate('ORDER_OVERVIEW_PDF_PAYMENTIMPACT') . ' ' . $this->_oData->translate(
                        'ORDER_OVERVIEW_PDF_NETTO'
                    )
                );
                $this->text(195 - $this->_oPdf->getStringWidth($sPayCostNetto), $iStartPos, $sPayCostNetto);

                if ($oConfig->getConfigParam('sAdditionalServVATCalcMethod') != 'proportional') {
                    $sVatValueText = $this->_oData->translate('ORDER_OVERVIEW_PDF_ZZGLVAT')
                        . $this->_oData->oxorder__oxpayvat->value . $this->_oData->translate(
                            'ORDER_OVERVIEW_PDF_PERCENTSUM'
                        );
                } else {
                    $sVatValueText = $this->_oData->translate('TOTAL_PLUS_PROPORTIONAL_VAT');
                }

                // payment VAT
                $iStartPos   += 4;
                $sPayCostVAT = $oLang->formatCurrency(
                        $this->_oData->getOrderPaymentPrice()
                                     ->getVATValue(),
                        $this->_oData->getCurrency()
                    ) . ' ' . $this->_oData->getCurrency()->name;
                $this->text(45, $iStartPos, $sVatValueText);
                $this->text(195 - $this->_oPdf->getStringWidth($sPayCostVAT), $iStartPos, $sPayCostVAT);
            }
            // if canceled order, reset value
        } else {
            // payment costs
            if ($this->_oData->oxorder__oxpaycost->value) {
                $iStartPos += 4;
                $sPayCost  = $oLang->formatCurrency(
                        $this->_oData->oxorder__oxpaycost->value,
                        $this->_oData->getCurrency()
                    ) . ' ' . $this->_oData->getCurrency()->name;
                $this->text(45, $iStartPos, $this->_oData->translate('ORDER_OVERVIEW_PDF_PAYMENTIMPACT'));
                $this->text(195 - $this->_oPdf->getStringWidth($sPayCost), $iStartPos, $sPayCost);
            }

            $iStartPos++;
        }
    }
    /**
     * Sets wrapping info to pdf
     *
     * @param int &$iStartPos text start position
     *
     */
    protected function _setWrappingInfo(&$iStartPos)
    {
        if ($this->_oData->oxorder__oxwrapcost->value) {
            $sAddString = '';
            $oLang      = oxRegistry::getLang();
            $oConfig    = oxRegistry::getConfig();

            //displaying wrapping VAT info
            if ($oConfig->getConfigParam('blShowVATForWrapping')) {
                if ($this->_oData->oxorder__oxwrapcost->value) {
                    // wrapping netto
                    $iStartPos      += 4;
                    $sWrapCostNetto = $oLang->formatCurrency(
                            $this->_oData->getOrderWrappingPrice()
                                         ->getNettoPrice(),
                            $this->_oData->getCurrency()
                        ) . ' ' . $this->_oData->getCurrency()->name;
                    $this->text(
                        45,
                        $iStartPos,
                        $this->_oData->translate('WRAPPING_COSTS') . ' ' . $this->_oData->translate(
                            'ORDER_OVERVIEW_PDF_NETTO'
                        )
                    );
                    $this->text(195 - $this->_oPdf->getStringWidth($sWrapCostNetto), $iStartPos, $sWrapCostNetto);
                    //$iStartPos++;

                    //wrapping VAT
                    $iStartPos    += 4;
                    $sWrapCostVAT = $oLang->formatCurrency(
                            $this->_oData->getOrderWrappingPrice()
                                         ->getVATValue(),
                            $this->_oData->getCurrency()
                        ) . ' ' . $this->_oData->getCurrency()->name;
                    $this->text(45, $iStartPos, $this->_oData->translate('ORDER_OVERVIEW_PDF_ZZGLVAT'));
                    $this->text(195 - $this->_oPdf->getStringWidth($sWrapCostVAT), $iStartPos, $sWrapCostVAT);
                    // $iStartPos++;
                }

                if ($this->_oData->oxorder__oxgiftcardcost->value) {
                    // wrapping netto
                    $iStartPos      += 4;
                    $sWrapCostNetto = $oLang->formatCurrency(
                            $this->_oData->getOrderGiftCardPrice()
                                         ->getNettoPrice(),
                            $this->_oData->getCurrency()
                        ) . ' ' . $this->_oData->getCurrency()->name;
                    $this->text(
                        45,
                        $iStartPos,
                        $this->_oData->translate('GIFTCARD_COSTS') . ' ' . $this->_oData->translate(
                            'ORDER_OVERVIEW_PDF_NETTO'
                        )
                    );
                    $this->text(195 - $this->_oPdf->getStringWidth($sWrapCostNetto), $iStartPos, $sWrapCostNetto);
                    //$iStartPos++;

                    //wrapping VAT
                    $iStartPos    += 4;
                    $sWrapCostVAT = $oLang->formatCurrency(
                            $this->_oData->getOrderGiftCardPrice()
                                         ->getVATValue(),
                            $this->_oData->getCurrency()
                        ) . ' ' . $this->_oData->getCurrency()->name;

                    if ($oConfig->getConfigParam('sAdditionalServVATCalcMethod') != 'proportional') {
                        $sVatValueText = $this->_oData->translate('ORDER_OVERVIEW_PDF_ZZGLVAT')
                            . $this->_oData->oxorder__oxgiftcardvat->value . $this->_oData->translate(
                                'ORDER_OVERVIEW_PDF_PERCENTSUM'
                            );
                    } else {
                        $sVatValueText = $this->_oData->translate('TOTAL_PLUS_PROPORTIONAL_VAT');
                    }

                    $this->text(45, $iStartPos, $sVatValueText);
                    $this->text(195 - $this->_oPdf->getStringWidth($sWrapCostVAT), $iStartPos, $sWrapCostVAT);
                    $iStartPos++;
                }
            } else {
                $iStartPos += 4;

                if ($this->_oData->oxorder__oxwrapcost->value) {
                    // wrapping cost
                    $sAddString = ' ' . $this->_oData->translate('ORDER_OVERVIEW_PDF_BRUTTO');
                    $sWrapCost  = $oLang->formatCurrency(
                            $this->_oData->oxorder__oxwrapcost->value,
                            $this->_oData->getCurrency()
                        ) . ' ' . $this->_oData->getCurrency()->name;
                    $this->text(
                        45,
                        $iStartPos,
                        $this->_oData->translate('WRAPPING_COSTS'/*'ORDER_OVERVIEW_PDF_WRAPPING'*/) . $sAddString
                    );
                    $this->text(195 - $this->_oPdf->getStringWidth($sWrapCost), $iStartPos, $sWrapCost);
                    $iStartPos++;
                }

                if ($this->_oData->oxorder__oxgiftcardcost->value) {
                    $iStartPos += 4;
                    // gift card cost
                    $sWrapCost = $oLang->formatCurrency(
                            $this->_oData->oxorder__oxgiftcardcost->value,
                            $this->_oData->getCurrency()
                        ) . ' ' . $this->_oData->getCurrency()->name;
                    $this->text(45, $iStartPos, $this->_oData->translate('GIFTCARD_COSTS') . $sAddString);
                    $this->text(195 - $this->_oPdf->getStringWidth($sWrapCost), $iStartPos, $sWrapCost);
                    $iStartPos++;
                }
            }
        }
    }
    /**
     * Sets payment info to pdf
     *
     * @param int &$iStartPos text start position
     *
     */
    protected function _setTsProtection(&$iStartPos)
    {
        $oLang   = oxRegistry::getLang();
        $oConfig = oxRegistry::getConfig();
        if ($this->_oData->oxorder__oxtsprotectcosts->value && $oConfig->getConfigParam('blShowVATForPayCharge')) {
            // payment netto
            $iStartPos     += 4;
            $sPayCostNetto = $oLang->formatCurrency(
                    $this->_oData->getOrderTsProtectionPrice()
                                 ->getNettoPrice(),
                    $this->_oData->getCurrency()
                ) . ' ' . $this->_oData->getCurrency()->name;
            $this->text(
                45,
                $iStartPos,
                $this->_oData->translate('ORDER_OVERVIEW_PDF_TSPROTECTION') . ' ' . $this->_oData->translate(
                    'ORDER_OVERVIEW_PDF_NETTO'
                )
            );
            $this->text(195 - $this->_oPdf->getStringWidth($sPayCostNetto), $iStartPos, $sPayCostNetto);

            // payment VAT
            $iStartPos   += 4;
            $sPayCostVAT = $oLang->formatCurrency(
                    $this->_oData->getOrderTsProtectionPrice()
                                 ->getVATValue(),
                    $this->_oData->getCurrency()
                ) . ' ' . $this->_oData->getCurrency()->name;
            $this->text(
                45,
                $iStartPos,
                $this->_oData->translate('ORDER_OVERVIEW_PDF_ZZGLVAT') . $oConfig->getConfigParam('dDefaultVAT')
                . $this->_oData->translate('ORDER_OVERVIEW_PDF_PERCENTSUM')
            );
            $this->text(195 - $this->_oPdf->getStringWidth($sPayCostVAT), $iStartPos, $sPayCostVAT);

            $iStartPos++;
        } elseif ($this->_oData->oxorder__oxtsprotectcosts->value) {
            $iStartPos += 4;
            $sPayCost  = $oLang->formatCurrency(
                    $this->_oData->oxorder__oxtsprotectcosts->value,
                    $this->_oData->getCurrency()
                ) . ' ' . $this->_oData->getCurrency()->name;
            $this->text(45, $iStartPos, $this->_oData->translate('ORDER_OVERVIEW_PDF_TSPROTECTION'));
            $this->text(195 - $this->_oPdf->getStringWidth($sPayCost), $iStartPos, $sPayCost);

            $iStartPos++;
        }
    }
    /**
     * Sets grand total order price to pdf
     *
     * @param int &$iStartPos text start position
     *
     */
    protected function _setGrandTotalPriceInfo(&$iStartPos)
    {
        $this->font($this->getFont(), 'B', 10);

        // total order sum
        $sTotalOrderSum = $this->_oData->getFormattedTotalOrderSum() . ' ' . $this->_oData->getCurrency()->name;
        $this->text(45, $iStartPos, $this->_oData->translate('ORDER_OVERVIEW_PDF_ALLSUM'));
        $this->text(195 - $this->_oPdf->getStringWidth($sTotalOrderSum), $iStartPos, $sTotalOrderSum);
        $iStartPos += 2;

        if ($this->_oData->oxorder__oxdelvat->value || $this->_oData->oxorder__oxwrapvat->value
            || $this->_oData->oxorder__oxpayvat->value) {
            $iStartPos += 2;
        }
    }
    /**
     * Sets payment method info to pdf
     *
     * @param int &$iStartPos text start position
     *
     */
    protected function _setPaymentMethodInfo(&$iStartPos)
    {
        $oPayment = oxNew('oxpayment');
        $oPayment->loadInLang($this->_oData->getSelectedLang(), $this->_oData->oxorder__oxpaymenttype->value);

        $text = $this->_oData->translate('ORDER_OVERVIEW_PDF_SELPAYMENT') . $oPayment->oxpayments__oxdesc->value;
        $this->font($this->getFont(), '', 10);
        $this->text(15, $iStartPos + 4, $text);
        $iStartPos += 4;
    }
}

/**
 * Order pdf generator class
 */
class Secupay_invoice_oxorder extends Secupay_invoice_oxorder_parent
{
    /**
     * PDF language
     * @var int
     */
    protected $_iSelectedLang = 0;

    /**
     * Cached active shop object
     * @var object
     */
    protected $_oActShop = null;

    /**
     * Order arctiles VAT's
     * @var array
     */
    protected $_aVATs = array();

    /**
     * Order currency object
     * @var object
     */
    protected $_oCur = null;
    /**
     * Generates order pdf report file
     *
     * @param string $sFilename name of report file
     * @param int    $iSelLang  active language
     *
     */
    public function genSecupayPdf($sFilename, $iSelLang = 0)
    {
        // setting pdf language
        $this->_iSelectedLang = $iSelLang;

        // setting invoice number
        if (!$this->oxorder__oxbillnr->value) {
            $this->oxorder__oxbillnr->setValue($this->getNextBillNum());
            $this->oxorder__oxbilldate->setValue(date('d.m.Y', mktime(0, 0, 0, date('m'), date('d'), date('Y'))));
            $this->save();
        }

        // initiating pdf engine
        //        $oPdf = oxNew( 'oxPDF' );
        $oPdf = new InvoicepdfPDF();
        $oPdf->setPrintHeader(false);
        $oPdf->open();

        // adding header
        $this->pdfHeader($oPdf);

        // adding info data
        $this->exportSecupay($oPdf);

        // adding footer
        $this->pdfFooter($oPdf);

        // outputting file to browser
        $oPdf->output($sFilename, 'I');
    }
    /**
     * Creating new page with shop logo. Returning position to continue data writing
     *
     * @param object $oPdf pdf document object
     *
     * @return int
     */
    public function pdfHeader($oPdf)
    {
        // adding new page ...
        $oPdf->addPage();

        // loading active shop
        $oShop = $this->_getActShop();

        //logo
        $myConfig = $this->getConfig();
        $aSize    = getimagesize($myConfig->getImageDir() . '/pdf_logo.jpg');
        $iMargin  = 195 - $aSize[0] * 0.2;
        $oPdf->setLink($oShop->oxshops__oxurl->value);
        $oPdf->image(
            $myConfig->getImageDir() . '/pdf_logo.jpg',
            $iMargin,
            10,
            $aSize[0] * 0.2,
            $aSize[1] * 0.2,
            '',
            $oShop->oxshops__oxurl->value
        );
        return 14 + $aSize[1] * 0.2;
    }
    /**
     * Returns active shop object
     *
     * @return oxshop $oUser
     */
    protected function _getActShop()
    {
        // shop is allready loaded
        if ($this->_oActShop !== null) return $this->_oActShop;

        $this->_oActShop = oxNew('oxshop');
        $this->_oActShop->load(
            $this->getConfig()
                 ->getShopId()
        );

        return $this->_oActShop;
    }
    /**
     * Exporting standard invoice pdf
     *
     * @param object $oPdf pdf document object
     *
     */
    public function exportSecupay($oPdf)
    {
        // preparing order curency info
        $myConfig  = $this->getConfig();
        $oPdfBlock = new SP_PdfBlock();

        $this->_oCur = $myConfig->getCurrencyObject($this->oxorder__oxcurrency->value);
        if (!$this->_oCur) {
            $this->_oCur = $myConfig->getActShopCurrencyObject();
        }

        // loading active shop
        $oShop = $this->_getActShop();

        // shop information
        $oPdf->setFont($oPdfBlock->getFont(), '', 6);
        $oPdf->text(
            15,
            55,
            $oShop->oxshops__oxname->getRawValue() . ' - ' . $oShop->oxshops__oxstreet->getRawValue() . ' - '
            . $oShop->oxshops__oxzip->value . ' - ' . $oShop->oxshops__oxcity->getRawValue()
        );

        // billing address
        $this->_setBillingAddressToPdf($oPdf);

        // delivery address
        if ($this->oxorder__oxdelsal->value) {
            $this->_setDeliveryAddressToPdf($oPdf);
        }

        // loading user
        $oUser = oxNew('oxuser');
        $oUser->load($this->oxorder__oxuserid->value);

        // user info
        $sText = $this->translate('ORDER_OVERVIEW_PDF_FILLONPAYMENT');
        $oPdf->setFont($oPdfBlock->getFont(), '', 5);
        $oPdf->text(195 - $oPdf->getStringWidth($sText), 55, $sText);

        // customer number
        $sCustNr = $this->translate('ORDER_OVERVIEW_PDF_CUSTNR') . ' ' . $oUser->oxuser__oxcustnr->value;
        $oPdf->setFont($oPdfBlock->getFont(), '', 7);
        $oPdf->text(195 - $oPdf->getStringWidth($sCustNr), 59, $sCustNr);

        // setting position if delivery address is used
        if ($this->oxorder__oxdelsal->value) {
            $iTop = 115;
        } else {
            $iTop = 91;
        }

        // shop city
        $sText = $oShop->oxshops__oxcity->getRawValue() . ', ' . date(
                'd.m.Y',
                strtotime($this->oxorder__oxbilldate->value)
            );
        $oPdf->setFont($oPdfBlock->getFont(), '', 10);
        $oPdf->text(195 - $oPdf->getStringWidth($sText), $iTop + 8, $sText);

        // shop VAT number
        if ($oShop->oxshops__oxvatnumber->value) {
            $sText = $this->translate('ORDER_OVERVIEW_PDF_TAXIDNR') . ' ' . $oShop->oxshops__oxvatnumber->value;
            $oPdf->text(195 - $oPdf->getStringWidth($sText), $iTop + 12, $sText);
            $iTop += 8;
        } else {
            $iTop += 4;
        }

        // invoice number
        $sText = $this->translate('ORDER_OVERVIEW_PDF_COUNTNR') . ' ' . $this->oxorder__oxbillnr->value;
        $oPdf->text(195 - $oPdf->getStringWidth($sText), $iTop + 8, $sText);

        // marking if order is canceled
        if ($this->oxorder__oxstorno->value == 1) {
            $this->oxorder__oxordernr->setValue(
                $this->oxorder__oxordernr->getRawValue() . '   ' . $this->translate('ORDER_OVERVIEW_PDF_STORNO'),
                oxField::T_RAW
            );
        }

        // order number
        $oPdf->setFont($oPdfBlock->getFont(), '', 12);
        $oPdf->text(
            15,
            $iTop,
            $this->translate('ORDER_OVERVIEW_PDF_PURCHASENR') . ' ' . $this->oxorder__oxordernr->value
        );

        // order date
        $oPdf->setFont($oPdfBlock->getFont(), '', 10);
        $aOrderDate = explode(' ', $this->oxorder__oxorderdate->value);
        $sOrderDate = oxRegistry::get("oxUtilsDate")
                                ->formatDBDate($aOrderDate[0]);
        $oPdf->text(
            15,
            $iTop + 8,
            $this->translate('ORDER_OVERVIEW_PDF_ORDERSFROM') . $sOrderDate . $this->translate(
                'ORDER_OVERVIEW_PDF_ORDERSAT'
            ) . $oShop->oxshops__oxurl->value
        );
        $iTop += 16;

        // product info header
        $oPdf->setFont($oPdfBlock->getFont(), '', 8);
        $oPdf->text(15, $iTop, $this->translate('ORDER_OVERVIEW_PDF_AMOUNT'));
        $oPdf->text(30, $iTop, $this->translate('ORDER_OVERVIEW_PDF_ARTID'));
        $oPdf->text(45, $iTop, $this->translate('ORDER_OVERVIEW_PDF_DESC'));
        $oPdf->text(135, $iTop, $this->translate('ORDER_OVERVIEW_PDF_VAT'));
        $oPdf->text(148, $iTop, $this->translate('ORDER_OVERVIEW_PDF_UNITPRICE'));
        $sText = $this->translate('ORDER_OVERVIEW_PDF_ALLPRICE');
        $oPdf->text(195 - $oPdf->getStringWidth($sText), $iTop, $sText);

        // separator line
        $iTop += 2;
        $oPdf->line(15, $iTop, 195, $iTop);

        // #345
        $siteH = $iTop;
        $oPdf->setFont($oPdfBlock->getFont(), '', 10);

        // order articles
        $this->_setOrderArticlesToPdf($oPdf, $siteH, true);

        // generating pdf file
        $oArtSumm = new SP_PdfArticleSummary($this, $oPdf);
        $iHeight  = $oArtSumm->generate($siteH);

        if ($siteH + $iHeight > 258) {
            $this->pdfFooter($oPdf);
            $iTop = $this->pdfHeader($oPdf);
            $oArtSumm->ajustHeight($iTop - $siteH);
            $siteH = $iTop;
        }

        $oArtSumm->run($oPdf);

        $siteH += $iHeight + 8;

        if ($siteH + $iHeight > 258) {
            $this->pdfFooter($oPdf);
            $iTop  = $this->pdfHeader($oPdf);
            $siteH = $iTop;
        }

        $this->addSecupayInfo($oPdf, $siteH);

        $oPdf->text(15, $siteH + 8, $this->translate('ORDER_OVERVIEW_PDF_GREETINGS'));
    }
    /**
     * Set billing address info to pdf
     *
     * @param object $oPdf pdf document object
     *
     */
    protected function _setBillingAddressToPdf($oPdf)
    {
        $oLang = oxRegistry::getLang();
        $sSal  = $this->oxorder__oxbillsal->value;
        try {
            $sSal = $oLang->translateString($this->oxorder__oxbillsal->value, $this->_iSelectedLang);
        } catch (Exception $e) {
        }
        $oPdfBlock = new SP_PdfBlock();
        $oPdf->setFont($oPdfBlock->getFont(), '', 10);
        $oPdf->text(15, 59, $sSal);
        $oPdf->text(
            15,
            63,
            $this->oxorder__oxbillfname->getRawValue() . ' ' . $this->oxorder__oxbilllname->getRawValue()
        );
        $oPdf->text(15, 67, $this->oxorder__oxbillcompany->getRawValue());
        $oPdf->text(15, 71, $this->oxorder__oxbillstreet->getRawValue() . ' ' . $this->oxorder__oxbillstreetnr->value);
        $oPdf->setFont($oPdfBlock->getFont(), 'B', 10);
        $oPdf->text(15, 75, $this->oxorder__oxbillzip->value . ' ' . $this->oxorder__oxbillcity->getRawValue());
        $oPdf->setFont($oPdfBlock->getFont(), '', 10);
        $oPdf->text(15, 79, $this->oxorder__oxbillcountry->getRawValue());
    }
    /**
     * Set delivery address info to pdf
     *
     * @param object $oPdf pdf document object
     *
     */
    protected function _setDeliveryAddressToPdf($oPdf)
    {
        $oLang = oxRegistry::getLang();
        $sSal  = $this->oxorder__oxdelsal->value;
        try {
            $sSal = $oLang->translateString($this->oxorder__oxdelsal->value, $this->_iSelectedLang);
        } catch (Exception $e) {
        }
        $oPdfBlock = new SP_PdfBlock();
        $oPdf->setFont($oPdfBlock->getFont(), '', 6);
        $oPdf->text(15, 87, $this->translate('ORDER_OVERVIEW_PDF_DELIVERYADDRESS'));
        $oPdf->setFont($oPdfBlock->getFont(), '', 10);
        $oPdf->text(15, 91, $sSal);
        $oPdf->text(
            15,
            95,
            $this->oxorder__oxdelfname->getRawValue() . ' ' . $this->oxorder__oxdellname->getRawValue()
        );
        $oPdf->text(15, 99, $this->oxorder__oxdelcompany->getRawValue());
        $oPdf->text(15, 103, $this->oxorder__oxdelstreet->getRawValue() . ' ' . $this->oxorder__oxdelstreetnr->value);
        $oPdf->setFont($oPdfBlock->getFont(), 'B', 10);
        $oPdf->text(15, 107, $this->oxorder__oxdelzip->value . ' ' . $this->oxorder__oxdelcity->getRawValue());
        $oPdf->setFont($oPdfBlock->getFont(), '', 10);
        $oPdf->text(15, 111, $this->oxorder__oxdelcountry->getRawValue());
    }
    /**
     * Returns translated string
     *
     * @param string $sString string to translate
     *
     * @return string
     */
    public function translate($sString)
    {
        return oxRegistry::getLang()
                         ->translateString($sString, $this->_iSelectedLang);
    }
    /**
     * Set order articles info and articles VAT's to pdf
     *
     * @param object  $oPdf        pdf document object
     * @param int    &$iStartPos   text start position from top
     * @param bool    $blShowPrice show articles prices / VAT info or not
     *
     */
    protected function _setOrderArticlesToPdf($oPdf, &$iStartPos, $blShowPrice = true)
    {
        if (!$this->_oArticles) {
            $this->_oArticles = $this->getOrderArticles(true);
        }

        $oCurr     = $this->getCurrency();
        $oPdfBlock = new SP_PdfBlock();
        // product list
        foreach ($this->_oArticles as $key => $oOrderArt) {
            // starting a new page ...
            if ($iStartPos > 243) {
                $this->pdfFooter($oPdf);
                $iStartPos = $this->pdfHeaderPlus($oPdf);
                $oPdf->setFont($oPdfBlock->getFont(), '', 10);
            } else {
                $iStartPos = $iStartPos + 4;
            }

            // sold amount
            $oPdf->text(
                20 - $oPdf->getStringWidth($oOrderArt->oxorderarticles__oxamount->value),
                $iStartPos,
                $oOrderArt->oxorderarticles__oxamount->value
            );

            // product number
            $oPdf->setFont($oPdfBlock->getFont(), '', 8);
            $oPdf->text(28, $iStartPos, $oOrderArt->oxorderarticles__oxartnum->value);

            // product title
            $oPdf->setFont($oPdfBlock->getFont(), '', 10);
            $oPdf->text(
                45,
                $iStartPos,
                substr(
                    strip_tags(
                        $this->_replaceExtendedChars($oOrderArt->oxorderarticles__oxtitle->getRawValue(), true)
                    ),
                    0,
                    58
                )
            );

            if ($blShowPrice) {
                $oLang = oxRegistry::getLang();

                // product VAT percent
                $oPdf->text(
                    140 - $oPdf->getStringWidth($oOrderArt->oxorderarticles__oxvat->value),
                    $iStartPos,
                    $oOrderArt->oxorderarticles__oxvat->value
                );

                // product price

                $dUnitPrice  = ($this->isNettoMode()) ? $oOrderArt->oxorderarticles__oxnprice->value
                    : $oOrderArt->oxorderarticles__oxbprice->value;
                $dTotalPrice = ($this->isNettoMode()) ? $oOrderArt->oxorderarticles__oxnetprice->value
                    : $oOrderArt->oxorderarticles__oxbrutprice->value;

                $sText = $oLang->formatCurrency($dUnitPrice, $this->_oCur) . ' ' . $this->_oCur->name;
                $oPdf->text(163 - $oPdf->getStringWidth($sText), $iStartPos, $sText);

                // total product price
                $sText = $oLang->formatCurrency($dTotalPrice, $this->_oCur) . ' ' . $this->_oCur->name;
                $oPdf->text(195 - $oPdf->getStringWidth($sText), $iStartPos, $sText);
            }

            // additional variant info
            if ($oOrderArt->oxorderarticles__oxselvariant->value) {
                $iStartPos = $iStartPos + 4;
                $oPdf->text(45, $iStartPos, substr($oOrderArt->oxorderarticles__oxselvariant->value, 0, 58));
            }
        }
    }
    /**
     * Returns order currency object
     *
     * @return object
     */
    public function getCurrency()
    {
        return $this->_oCur;
    }
    /**
     * Formats pdf page footer
     *
     * @param object $oPdf pdf document object
     *
     */
    public function pdfFooter($oPdf)
    {
        $oShop = $this->_getActShop();

        $oPdf->line(15, 272, 195, 272);

        $oPdfBlock = new SP_PdfBlock();
        /* column 1 - company name, shop owner info, shop address */
        $oPdf->setFont($oPdfBlock->getFont(), '', 7);
        $oPdf->text(15, 275, strip_tags($oShop->oxshops__oxcompany->getRawValue()));
        $oPdf->text(
            15,
            278,
            strip_tags($oShop->oxshops__oxfname->getRawValue()) . ' ' . strip_tags(
                $oShop->oxshops__oxlname->getRawValue()
            )
        );
        $oPdf->text(15, 281, strip_tags($oShop->oxshops__oxstreet->getRawValue()));
        $oPdf->text(
            15,
            284,
            strip_tags($oShop->oxshops__oxzip->value) . ' ' . strip_tags($oShop->oxshops__oxcity->getRawValue())
        );
        $oPdf->text(15, 287, strip_tags($oShop->oxshops__oxcountry->getRawValue()));

        /* column 2 - phone, fax, url, email address */
        $oPdf->text(
            85,
            275,
            $this->translate('ORDER_OVERVIEW_PDF_PHONE') . strip_tags($oShop->oxshops__oxtelefon->value)
        );
        $oPdf->text(
            85,
            278,
            $this->translate('ORDER_OVERVIEW_PDF_FAX') . strip_tags($oShop->oxshops__oxtelefax->value)
        );
        $oPdf->text(85, 281, strip_tags($oShop->oxshops__oxurl->value));
        $oPdf->text(85, 284, strip_tags($oShop->oxshops__oxorderemail->value));

        /* column 3 - bank information */
        $oPdf->text(150, 275, strip_tags($oShop->oxshops__oxbankname->getRawValue()));
        $oPdf->text(
            150,
            278,
            $this->translate('ORDER_OVERVIEW_PDF_ACCOUNTNR') . strip_tags($oShop->oxshops__oxbanknumber->value)
        );
        $oPdf->text(
            150,
            281,
            $this->translate('ORDER_OVERVIEW_PDF_BANKCODE') . strip_tags($oShop->oxshops__oxbankcode->value)
        );
        $oPdf->text(150, 284, strip_tags($oShop->oxshops__oxvatnumber->value));
        $oPdf->text(150, 287, '');
    }
    /**
     * Adds shop logo to page header. Returns position for next texts in pdf
     *
     * @param object $oPdf pdf document object
     *
     * @return int
     */
    public function pdfHeaderPlus($oPdf)
    {
        // new page with shop logo
        $this->pdfHeader($oPdf);

        $oPdfBlock = new SP_PdfBlock();
        // column names
        $oPdf->setFont($oPdfBlock->getFont(), '', 8);
        $oPdf->text(15, 50, $this->translate('ORDER_OVERVIEW_PDF_AMOUNT'));
        $oPdf->text(30, 50, $this->translate('ORDER_OVERVIEW_PDF_ARTID'));
        $oPdf->text(45, 50, $this->translate('ORDER_OVERVIEW_PDF_DESC'));
        $oPdf->text(160, 50, $this->translate('ORDER_OVERVIEW_PDF_UNITPRICE'));
        $sText = $this->translate('ORDER_OVERVIEW_PDF_ALLPRICE');
        $oPdf->text(195 - $oPdf->getStringWidth($sText), 50, $sText);

        // line separator
        $oPdf->line(15, 52, 195, 52);

        return 56;
    }
    /**
     * Replaces some special characters to HTML compatible symbol codes.
     * SWITCHED OFF NOW ( 2.2 )
     *
     * @param string $sValue    initial value
     * @param bool   $blReverse (default false) if false - checks if we do have already htmlentities inside
     *
     * @return string
     */
    protected function _replaceExtendedChars($sValue, $blReverse = false)
    {
        // we need to replace this for compatibility with XHTML
        // as this function causes a lot of trouble with editor
        // we switch it off, even if this means that fields do not validate through xhtml
        // return $sValue;

        // we need to replace this for compatibility with XHTML
        $aReplace = array(chr(169) => "&copy;", chr(128) => "&euro;", "\"" => "&quot;", "'" => "&#039;");

        // #899C reverse html entities and references transformation is used in invoicepdf module
        // so this part must be enabled. Now it works with html references like &#123;
        if ($blReverse) {
            // replace now
            if (version_compare(PHP_VERSION, '5.3.4') >= 0) {
                $encoding = $this->getConfig()->isUtf() ? 'UTF-8' : 'ISO-8859-1';
                $aTransTbl = get_html_translation_table(HTML_ENTITIES, ENT_COMPAT, $encoding);
            } else {
                $aTransTbl = get_html_translation_table(HTML_ENTITIES, ENT_COMPAT);
            }

            $aTransTbl = array_flip($aTransTbl) + array_flip($aReplace);
            $sValue = strtr($sValue, $aTransTbl);
            $sValue = getStr()->preg_replace_callback('/\&\#([0-9]+)\;/m', create_function('$matches', "return chr(\$matches[1]);") , $sValue);
        }

        return $sValue;
    }
    /**
     * Function that adds secupay information to the generated PDF file
     * Function is here so there wont be many difference from the source of this module
     *
     * @param object oPdf - generated pdf variable
     * @param int iStartPos - horizontal position
     */
    private function addSecupayInfo(&$oPdf, &$iStartPos)
    {
        $transactionInfo = $this->_getTransactionInfo();
        secupay_log::log(true, "gettransinfo: " . print_r($transactionInfo, true));

        $invoice_data = secupay_invoice_data::secupay_get_invoice_data($transactionInfo['hash']);

        $days_until_billdate = intval(
            $this->getConfig()
                 ->getConfigParam('SECUPAY_DAYS_TO_BILLDATE')
        );
        if (empty($days_until_billdate) || !is_numeric($days_until_billdate)) {
            $days_until_billdate = 10;
        }

        if (!empty($invoice_data)) {
            $billdate = $this->oxorder__oxbilldate->value;
            if (!empty($invoice_data->shipping_date) and $invoice_data->shipped == true) {
                $billdate = $invoice_data->shipping_date;
            }
            $oPdfBlock = new SP_PdfBlock();
            $oPdf->setFont($oPdfBlock->getFont(), '', 10);
            if ($transactionInfo['payment_method'] == 'secupay_invoice') {
                $oPdf->text(
                    15,
                    $iStartPos - 4,
                    $this->translate('ORDER_OVERVIEW_PDF_PAYUPTO') . date(
                        'd.m.Y',
                        strtotime(
                            '+' . $days_until_billdate . ' day',
                            strtotime($billdate)
                        )
                    )
                );
            }
            if ($transactionInfo['payment_method'] == 'secupay_prepay') {
                $oPdf->text(15, $iStartPos - 4, $this->_translateSecupayString('SECUPAY_PREPAY_BILLDATE'));
            }
            $oPdf->text(
                15,
                $iStartPos + 8,
                $this->_translateSecupayString(
                    str_replace(
                        '[recipient_legal]',
                        $invoice_data->recipient_legal,
                        $this->_translateSecupayString('SECUPAY_ADDRESS_INVOICE')
                    ),
                    true
                )
            );
            $oPdf->setFont($oPdfBlock->getFont(), 'B', 10);
            $oPdf->text(15, $iStartPos + 12, $this->_translateSecupayString('SECUPAY_PAYMENT_HEADER'));
            $oPdf->setFont($oPdfBlock->getFont(), '', 10);
            $oPdf->text(
                15,
                $iStartPos + 22,
                $this->_translateSecupayString('SECUPAY_RECIPIENT') . $this->_translateSecupayString(
                    $invoice_data->transfer_payment_data->accountowner,
                    true
                )
            );
            $oPdf->text(
                15,
                $iStartPos + 26,
                $this->_translateSecupayString('SECUPAY_IBAN') . $invoice_data->transfer_payment_data->iban . ', '
                . $this->_translateSecupayString('SECUPAY_BIC') . $invoice_data->transfer_payment_data->bic
            );
            $oPdf->text(
                15,
                $iStartPos + 30,
                $this->_translateSecupayString('SECUPAY_BANK_NAME') . $this->_translateSecupayString(
                    $invoice_data->transfer_payment_data->bankname,
                    true
                )
            );
            $purposeText = $this->_translateSecupayString('SECUPAY_PURPOSE');
            $width       = $oPdf->getStringWidth($purposeText);
            // print purpose: in normal font
            $oPdf->text(15, $iStartPos + 34, $purposeText);
            // set font to bold and print other part of line:
            $oPdf->setFont($oPdfBlock->getFont(), 'B', 10);
            $oPdf->text(
                15 + $width,
                $iStartPos + 34,
                ' ' . $this->_translateSecupayString($invoice_data->transfer_payment_data->purpose, true)
            );
            // set font back to normal
            if ($transactionInfo['payment_method'] == 'secupay_invoice') {
                $oPdf->setFont($oPdfBlock->getFont(), '', 10);
                $oPdf->text(15, $iStartPos + 44, $this->_translateSecupayString('SECUPAY_INFO_BOTTOM_1'));
                $oPdf->text(
                    15,
                    $iStartPos + 48,
                    $this->_translateSecupayString('SECUPAY_INFO_BOTTOM_2') . ' ' . $transactionInfo['iframe_url']
                );

                // if iframe_url is not empty, then add qrcode image with an iframe url to the generated PDF file
                if (!empty($invoice_data->payment_link)) {
                    $qr_code_img = $invoice_data->payment_qr_image_url;
                    $aSize       = getimagesize($qr_code_img);
                    $iMargin     = 195 - $aSize[0] * 0.2;
                    $oPdf->image(
                        $qr_code_img,
                        $iMargin,
                        $iStartPos + 14,
                        $aSize[0] * 0.2,
                        $aSize[1] * 0.2,
                        substr(strrchr($aSize['mime'], '/'), 1),
                        $invoice_data->payment_link
                    );
                }
            }
            $iStartPos += 48;

        }
    }
    /**
     * Get Iframe Url for order
     *
     * @return array
     * @throws DatabaseConnectionException
     * @throws DatabaseErrorException
     */
    public function _getTransactionInfo()
    {
        $oID = $this->oxorder__oxid->value;

        $oDb = oxDb::getDb();
        $oDb->SetFetchMode(oxdb::FETCH_MODE_ASSOC);
        $sglQuery = "SELECT a.transaction_id as trans_id, DATE_FORMAT(a.created, '%Y%m%d') created, concat(url.iframe_url, a.hash) as iframe_url, a.hash as hash,a.payment_method as payment_method FROM oxsecupay a
                LEFT JOIN oxsecupay_iframe_url url ON url.iframe_url_id = a.iframe_url_id
                WHERE a.oxorder_id = ? ORDER BY created DESC LIMIT 1";
        $result   = $oDb->getRow($sglQuery, [$oID]);

        return $result;
    }
    /**
     * translate string and decode, if option is not set
     * module language files are utf8, has to be decoded for shops without utf8
     *
     * @param string  $translation_string
     * @param boolean $skip_translation
     *
     * @return string
     */
    private function _translateSecupayString($translation_string, $skip_translation = false)
    {
        $use_utf8 = $this->getConfig()
                         ->getConfigParam('SECUPAY_USE_UTF8');

        if (!$skip_translation) {
            $translation = $this->translate($translation_string);
        } else {
            $translation = $translation_string;
        }

        if (!$use_utf8) {
            $translation = utf8_decode($translation);
        }

        return $translation;
    }
    /**
     * Returns order articles VATS's
     *
     * @return array
     */
    public function getVats()
    {
        // for older orders
        return $this->getProductVats(false);
    }
    /**
     * Returns order currency object
     *
     * @return int
     */
    public function getSelectedLang()
    {
        return $this->_iSelectedLang;
    }
}
