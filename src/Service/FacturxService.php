<?php

namespace App\Service;

use Atgp\FacturX\Writer;
use Atgp\FacturX\Reader;
use Atgp\FacturX\XsdValidator;

class FacturxService
{
    public function __construct(
        private FacturxXmlService $xmlService
    ) {
    }

    /**
     * Generate a Factur-X PDF (PDF/A-3) from a source PDF + invoice data.
     *
     * @param string $sourcePdfPath Path to original PDF (template, invoice layout…)
     * @param array $invoiceData Invoice array
     * @param string $profile Factur-X profile (MINIMUM, BASIC-WL, BASIC, EN16931)
     * @param string $outPdfPath Where to write the output PDF
     * @return string path to generated PDF
     */
    public function generatePdf(string $sourcePdfPath, array $invoiceData, string $profile, string $outPdfPath): string
    {
        // Generate XML from the invoice data using our FacturxXmlService
        $xml = $this->xmlService->generate($invoiceData, $profile);

        // Use ATGP writer to embed XML into PDF
        $writer = new Writer();
        $pdfContent = $writer->generate($sourcePdfPath, $xml);

        file_put_contents($outPdfPath, $pdfContent);
        return $outPdfPath;
    }

    /**
     * Extract and validate a Factur-X PDF
     *
     * @param string $pdfPath
     * @return array
     * [
     *    'valid' => bool,
     *    'xml' => string|null,
     *    'xsd_valid' => bool,
     *    'xsd_errors' => array|null,
     *    'profile' => string|null,
     *    'issuer' => string|null,
     *    'recipient' => string|null,
     *    'authenticity' => [ 'has_signature' => bool, 'notes' => string|null ]
     * ]
     */
    public function readAndValidatePdf(string $pdfPath): array
    {
        $reader = new Reader();
        $validator = new XsdValidator();

        $result = [
            'valid' => false,
            'xml' => null,
            'xsd_valid' => false,
            'xsd_errors' => null,
            'profile' => null,
            'issuer' => null,
            'recipient' => null,
            'authenticity' => [
                'has_signature' => false,
                'notes' => null
            ],
        ];

        // 1. Extract XML
        try {
            $xml = $reader->extractXML($pdfPath);
            $result['xml'] = $xml;
        } catch (\Throwable $e) {
            $result['xsd_errors'] = ["XML extraction failed: " . $e->getMessage()];
            return $result;
        }

        // 2. Validate XML against XSD
        try {
            $isValid = $validator->validate($xml);
            $result['xsd_valid'] = $isValid;
            if (!$isValid) {
                $result['xsd_errors'] = $validator->getErrors();
            }
        } catch (\Throwable $e) {
            $result['xsd_valid'] = false;
            $result['xsd_errors'] = ["XSD validation failed: " . $e->getMessage()];
        }

        // 3. Detect profile and parties (issuer/recipient)
        try {
            $dom = new \DOMDocument();
            $dom->loadXML($xml);
            $xpath = new \DOMXPath($dom);

            // Try to detect profile through guideline ID in header
            $xpath->registerNamespace('ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100');
            $xpath->registerNamespace('rsm', 'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100');

            $guideline = $xpath->query('//ram:GuidelineSpecifiedDocumentContextParameter/ram:ID');
            if ($guideline->length) {
                $guidelineId = trim($guideline->item(0)->nodeValue);
                $result['profile'] = $this->mapGuidelineToProfile($guidelineId);
            }

            // Issuer (SellerTradeParty)
            $seller = $xpath->query('//ram:SellerTradeParty/ram:Name');
            if ($seller->length) {
                $result['issuer'] = trim($seller->item(0)->nodeValue);
            }

            // Recipient (BuyerTradeParty)
            $buyer = $xpath->query('//ram:BuyerTradeParty/ram:Name');
            if ($buyer->length) {
                $result['recipient'] = trim($buyer->item(0)->nodeValue);
            }

        } catch (\Throwable $e) {
            $result['xsd_errors'][] = 'XML parsing error: ' . $e->getMessage();
        }

        // 4. Check for signatures (basic)
        $pdfContent = file_get_contents($pdfPath);
        if (strpos($pdfContent, '/ByteRange') !== false) {
            $result['authenticity']['has_signature'] = true;
            $result['authenticity']['notes'] = 'PDF contains a signature structure (/ByteRange). Full cryptographic verification is out of scope.';
        }

        $result['valid'] = $result['xsd_valid'];
        return $result;
    }

    /**
     * Map guideline ID to Factur-X profile
     */
    private function mapGuidelineToProfile(string $guidelineId): ?string
    {
        return match (true) {
            str_contains($guidelineId, 'minimum')  => 'MINIMUM',
            str_contains($guidelineId, 'basicwl')  => 'BASIC-WL',
            str_contains($guidelineId, 'basic')    => 'BASIC',
            str_contains($guidelineId, 'en16931')  => 'EN16931',
            default => null
        };
    }
}
