<?php

namespace App\Service;

use DOMDocument;
use DOMElement;

class FacturxXmlService
{
    /**
     * Generate Factur-X XML according to profile.
     *
     * @param array $invoiceData Structured invoice data
     * @param string $profile One of MINIMUM, BASIC-WL, BASIC, EN16931
     * @return string XML content
     * @throws \InvalidArgumentException
     */
    public function generate(array $invoiceData, string $profile = 'MINIMUM'): string
    {
        $profile = strtoupper($profile);

        return match ($profile) {
            'MINIMUM'    => $this->buildMinimumXml($invoiceData),
            'BASIC-WL'   => $this->buildBasicXml($invoiceData, 'BASIC-WL'),
            'BASIC'      => $this->buildBasicXml($invoiceData, 'BASIC'),
            'EN16931'    => $this->buildEn16931Xml($invoiceData),
            default      => throw new \InvalidArgumentException("Unknown Factur-X profile: $profile")
        };
    }

    /**
     * Minimal profile XML structure
     * @param array<int,mixed> $invoiceData
     */
    private function buildMinimumXml(array $invoiceData): string
    {
        $doc = $this->createBaseDocument('MINIMUM');

        $root = $doc->documentElement;

        // Seller
        $this->appendParty($doc, $root, 'SellerTradeParty', $invoiceData['seller'] ?? []);

        // Buyer
        $this->appendParty($doc, $root, 'BuyerTradeParty', $invoiceData['buyer'] ?? []);

        // Invoice ID and date
        $this->appendElement($doc, $root, 'ExchangedDocument/ID', $invoiceData['id'] ?? 'INV-0001');
        $this->appendElement($doc, $root, 'ExchangedDocument/IssueDateTime/DateTimeString', $invoiceData['date'] ?? date('Ymd'));

        // Amount
        $this->appendElement($doc, $root, 'SupplyChainTradeTransaction/ApplicableSupplyChainTradeSettlement/SpecifiedTradeSettlementMonetarySummation/GrandTotalAmount', $invoiceData['total'] ?? '0.00');

        return $doc->saveXML();
    }

    /**
     * BASIC-WL and BASIC share the same skeleton
     * @param array<int,mixed> $invoiceData
     */
    private function buildBasicXml(array $invoiceData, string $profile): string
    {
        $doc = $this->createBaseDocument($profile);
        $root = $doc->documentElement;

        $this->appendParty($doc, $root, 'SellerTradeParty', $invoiceData['seller'] ?? []);
        $this->appendParty($doc, $root, 'BuyerTradeParty', $invoiceData['buyer'] ?? []);

        $this->appendElement($doc, $root, 'ExchangedDocument/ID', $invoiceData['id'] ?? 'INV-0001');
        $this->appendElement($doc, $root, 'ExchangedDocument/IssueDateTime/DateTimeString', $invoiceData['date'] ?? date('Ymd'));

        // Line items
        if (!empty($invoiceData['lines'])) {
            $transaction = $this->appendElement($doc, $root, 'SupplyChainTradeTransaction');

            foreach ($invoiceData['lines'] as $line) {
                $item = $this->appendElement($doc, $transaction, 'IncludedSupplyChainTradeLineItem');
                $this->appendElement($doc, $item, 'SpecifiedTradeProduct/Name', $line['description'] ?? '');
                $this->appendElement($doc, $item, 'SpecifiedLineTradeAgreement/GrossPriceProductTradePrice/ChargeAmount', $line['price'] ?? '0.00');
                $this->appendElement($doc, $item, 'SpecifiedLineTradeDelivery/BilledQuantity', $line['quantity'] ?? '1');
            }
        }

        // Totals
        $this->appendElement($doc, $root, 'SupplyChainTradeTransaction/ApplicableSupplyChainTradeSettlement/SpecifiedTradeSettlementMonetarySummation/GrandTotalAmount', $invoiceData['total'] ?? '0.00');

        return $doc->saveXML();
    }

    /**
     * EN16931 full structure (skeleton only here)
     * @param array<int,mixed> $invoiceData
     */
    private function buildEn16931Xml(array $invoiceData): string
    {
        $doc = $this->createBaseDocument('EN16931');
        $root = $doc->documentElement;

        $this->appendParty($doc, $root, 'SellerTradeParty', $invoiceData['seller'] ?? []);
        $this->appendParty($doc, $root, 'BuyerTradeParty', $invoiceData['buyer'] ?? []);

        $this->appendElement($doc, $root, 'ExchangedDocument/ID', $invoiceData['id'] ?? 'INV-0001');
        $this->appendElement($doc, $root, 'ExchangedDocument/IssueDateTime/DateTimeString', $invoiceData['date'] ?? date('Ymd'));

        // Example tax and totals section
        $this->appendElement($doc, $root, 'SupplyChainTradeTransaction/ApplicableSupplyChainTradeSettlement/SpecifiedTradeSettlementMonetarySummation/LineTotalAmount', $invoiceData['subtotal'] ?? '0.00');
        $this->appendElement($doc, $root, 'SupplyChainTradeTransaction/ApplicableSupplyChainTradeSettlement/SpecifiedTradeSettlementMonetarySummation/TaxTotalAmount', $invoiceData['tax'] ?? '0.00');
        $this->appendElement($doc, $root, 'SupplyChainTradeTransaction/ApplicableSupplyChainTradeSettlement/SpecifiedTradeSettlementMonetarySummation/GrandTotalAmount', $invoiceData['total'] ?? '0.00');

        return $doc->saveXML();
    }

    /**
     * Create a DOMDocument with base namespaces for Factur-X
     */
    private function createBaseDocument(string $profile): DOMDocument
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;

        $root = $doc->createElementNS(
            'urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100',
            'rsm:CrossIndustryInvoice'
        );
        $root->setAttribute('xmlns:ram', 'urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100');
        $root->setAttribute('xmlns:udt', 'urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100');
        $root->setAttribute('xmlns:qdt', 'urn:un:unece:uncefact:data:standard:QualifiedDataType:100');

        // Indicate Conformance Level
        $exchanged = $doc->createElement('rsm:ExchangedDocumentContext');
        $context = $doc->createElement('ram:GuidelineSpecifiedDocumentContextParameter');
        $id = $doc->createElement('ram:ID', $this->profileUri($profile));
        $context->appendChild($id);
        $exchanged->appendChild($context);
        $root->appendChild($exchanged);

        $doc->appendChild($root);
        return $doc;
    }

    /**
     * Append an element to a path like A/B/C
     */
    private function appendElement(DOMDocument $doc, DOMElement $parent, string $path, string $value = null): DOMElement
    {
        $segments = explode('/', $path);
        $node = $parent;

        foreach ($segments as $seg) {
            // if child already exists, reuse it
            $existing = null;
            foreach ($node->childNodes as $child) {
                if ($child instanceof DOMElement && $child->localName === $seg) {
                    $existing = $child;
                    break;
                }
            }

            if ($existing) {
                $node = $existing;
            } else {
                $new = $doc->createElement("ram:$seg");
                $node->appendChild($new);
                $node = $new;
            }
        }

        if ($value !== null) {
            $node->nodeValue = $value;
        }

        return $node;
    }

    /**
     * Append a Seller or Buyer party
     * @param array<int,mixed> $data
     */
    private function appendParty(DOMDocument $doc, DOMElement $root, string $partyTag, array $data): void
    {
        $applicable = $this->appendElement($doc, $root, 'SupplyChainTradeTransaction/ApplicableHeaderTradeAgreement');
        $party = $doc->createElement("ram:$partyTag");

        $name = $doc->createElement('ram:Name', $data['name'] ?? 'Unknown');
        $party->appendChild($name);

        if (!empty($data['vat'])) {
            $taxId = $doc->createElement('ram:SpecifiedTaxRegistration');
            $id = $doc->createElement('ram:ID', $data['vat']);
            $taxId->appendChild($id);
            $party->appendChild($taxId);
        }

        $applicable->appendChild($party);
    }

    /**
     * Map profile name to official guideline ID (used in ConformanceLevel)
     */
    private function profileUri(string $profile): string
    {
        return match (strtoupper($profile)) {
            'MINIMUM'   => 'urn:factur-x.eu:1p0:minimum',
            'BASIC-WL'  => 'urn:factur-x.eu:1p0:basicwl',
            'BASIC'     => 'urn:factur-x.eu:1p0:basic',
            'EN16931'   => 'urn:factur-x.eu:1p0:en16931',
            default     => 'urn:factur-x.eu:1p0:basic',
        };
    }
}
