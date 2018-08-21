<?php
/**
 * Class that overrides PDF module generation for OXID
 *
 */

/**
 * Overrides the order_overview.php
 */
class Secupay_Order_Overview extends Secupay_Order_Overview_parent
{
    /**
     * Returns pdf export state - can export or not
     *
     * @return bool
     * @throws \OxidEsales\Eshop\Core\Exception\DatabaseConnectionException
     */
    public function canSecupayExport()
    {
        $blCan = false;
        $oModule = oxNew('oxmodule');
        $oModule->load('secupay_invoice');
        $active_invoice = false;
        $active_prepay = false;
        if ( $oModule->isActive() ) {
            $active_invoice=true;
        }
        $oModule->load('secupay_prepay');
        if ( $oModule->isActive() ) {
            $active_prepay=true;
        }
        if ($active_invoice || $active_prepay ) {
            $oDb = oxDb::getDb();
            $sOrderId = $this->getEditObjectId();
            $sTable = getViewName( "oxorderarticles" );
            $sQ = "SELECT count(a.oxid) FROM {$sTable} a INNER JOIN oxsecupay sec ON (sec.payment_method = 'secupay_invoice' or sec.payment_method = 'secupay_prepay') AND sec.oxorder_id = a.oxorderid WHERE a.oxorderid = ? AND a.oxstorno = 0";
            $blCan = (bool) $oDb->getOne($sQ, [$sOrderId]);
        }
        return $blCan;
    }

    /**
     * Performs PDF export to user (outputs file to save).
     *
     */
    public function createSecupayPDF()
    {
        $soxId = $this->getEditObjectId();
        if ( $soxId != "-1" && isset( $soxId ) ) {
            // load object
            $oOrder = oxNew( "Secupay_invoice_oxorder" );
            if ( $oOrder->load( $soxId ) ) {
                $oUtils = oxRegistry::getUtils();
                $sTrimmedBillName = trim($oOrder->oxorder__oxbilllname->getRawValue());
                $sFilename = $oOrder->oxorder__oxordernr->value . "_" . $sTrimmedBillName . ".pdf";
                $sFilename = str_replace(" ", "_", $sFilename);
                ob_start();
                $myConfig = $this->getConfig();
                //$oOrder->genSecupayPdf( $sFilename, oxConfig::getParameter( "pdflanguage" ) );
                $oOrder->genSecupayPdf( $sFilename, $myConfig->getRequestParameter( "pdflanguage" ) );
                $sPDF = ob_get_contents();
                ob_end_clean();
                $oUtils->setHeader( "Pragma: public" );
                $oUtils->setHeader( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
                $oUtils->setHeader( "Expires: 0" );
                $oUtils->setHeader( "Content-type: application/pdf" );
                $oUtils->setHeader( "Content-Disposition: attachment; filename=".$sFilename );
                oxRegistry::getUtils()->showMessageAndExit( $sPDF );
            }
        }
    }
}