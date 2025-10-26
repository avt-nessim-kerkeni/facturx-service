<?php

namespace App\Service;

use DOMDocument;
use DOMElement;

class FacturxXmlService
{
    /**
     * @param array<string, mixed> $invoiceData
     */
    public function generate(array $invoiceData, string $profile = 'MINIMUM'): string
    {
        $profile = strtoupper($profile);

        return match ($profile) {
            'MINIMUM'    => $this->buildMinimumXml($invoiceData),
            'BASICWL'    => $this->buildBasicXml($invoiceData, 'BASICWL'),
            'BASIC'      => $this->buildBasicXml($invoiceData, 'BASIC'),
            'EN16931'    => $this->buildEn16931Xml($invoiceData),
            default      => throw new \InvalidArgumentException("Unknown Factur-X profile: $profile")
        };
    }

    /**
     * @param array<string, mixed> $invoiceData
     */
    private function buildMinimumXml(array $invoiceData): string
    {
        $doc = $this->createBaseDocument('MINIMUM');
        $root = $doc->documentElement;

        if (!$root) {
            throw new \RuntimeException('Could not create root element');
        }

        $root->appendChild($this->createExchangedDocument($doc, $invoiceData['ExchangedDocument'] ?? []));
        $root->appendChild($this->createSupplyChainTradeTransaction($doc, $invoiceData['SupplyChainTradeTransaction'] ?? [], 'MINIMUM'));

        $xml = $doc->saveXML();
        if ($xml === false) {
            throw new \RuntimeException('Could not save XML');
        }
        return $xml;
    }

    /**
     * @param array<string, mixed> $invoiceData
     */
    private function buildBasicXml(array $invoiceData, string $profile): string
    {
        $doc = $this->createBaseDocument($profile);
        $root = $doc->documentElement;

        if (!$root) {
            throw new \RuntimeException('Could not create root element');
        }

        $root->appendChild($this->createExchangedDocument($doc, $invoiceData['ExchangedDocument'] ?? []));
        $root->appendChild($this->createSupplyChainTradeTransaction($doc, $invoiceData['SupplyChainTradeTransaction'] ?? [], $profile));

        $xml = $doc->saveXML();
        if ($xml === false) {
            throw new \RuntimeException('Could not save XML');
        }
        return $xml;
    }

    /**
     * @param array<string, mixed> $invoiceData
     */
    private function buildEn16931Xml(array $invoiceData): string
    {
        $doc = $this->createBaseDocument('EN16931');
        $root = $doc->documentElement;

        if (!$root) {
            throw new \RuntimeException('Could not create root element');
        }

        $root->appendChild($this->createExchangedDocument($doc, $invoiceData['ExchangedDocument'] ?? []));
        $root->appendChild($this->createSupplyChainTradeTransaction($doc, $invoiceData['SupplyChainTradeTransaction'] ?? [], 'EN16931'));

        $xml = $doc->saveXML();
        if ($xml === false) {
            throw new \RuntimeException('Could not save XML');
        }
        return $xml;
    }

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

        $doc->appendChild($root);

        $root->appendChild($this->createExchangedDocumentContext($doc, $profile));

        return $doc;
    }

    private function createExchangedDocumentContext(DOMDocument $doc, string $profile): DOMElement
    {
        $exchanged = $doc->createElement('rsm:ExchangedDocumentContext');
        $context = $doc->createElement('ram:GuidelineSpecifiedDocumentContextParameter');
        $id = $doc->createElement('ram:ID', $this->profileUri($profile));
        $context->appendChild($id);
        $exchanged->appendChild($context);
        return $exchanged;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createExchangedDocument(DOMDocument $doc, array $data): DOMElement
    {
        $exchangedDocument = $doc->createElement('rsm:ExchangedDocument');
        $this->appendSimpleElement($doc, $exchangedDocument, 'ram:ID', $data['ID'] ?? '');
        $this->appendSimpleElement($doc, $exchangedDocument, 'ram:TypeCode', $data['TypeCode'] ?? '380');
        $issueDateTime = $this->appendSimpleElement($doc, $exchangedDocument, 'ram:IssueDateTime');
        $dateTimeString = $this->appendSimpleElement($doc, $issueDateTime, 'udt:DateTimeString', $data['IssueDateTime'] ?? '');
        $dateTimeString->setAttribute('format', '102');
        return $exchangedDocument;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createSupplyChainTradeTransaction(DOMDocument $doc, array $data, string $profile): DOMElement
    {
        $transaction = $doc->createElement('rsm:SupplyChainTradeTransaction');

        if ($profile === 'BASIC' || $profile === 'EN16931') {
            foreach ($data['IncludedSupplyChainTradeLineItem'] ?? [] as $lineData) {
                $transaction->appendChild($this->createIncludedSupplyChainTradeLineItem($doc, $lineData, $profile));
            }
        }

        $tradeAgreement = $this->createApplicableHeaderTradeAgreement($doc, $data['ApplicableHeaderTradeAgreement'] ?? [], $profile);
        $transaction->appendChild($tradeAgreement);

        $tradeDelivery = $doc->createElement('ram:ApplicableHeaderTradeDelivery');
        $transaction->appendChild($tradeDelivery);

        $tradeSettlement = $this->createApplicableHeaderTradeSettlement($doc, $data['ApplicableHeaderTradeSettlement'] ?? [], $profile);
        $transaction->appendChild($tradeSettlement);

        return $transaction;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createIncludedSupplyChainTradeLineItem(DOMDocument $doc, array $data, string $profile): DOMElement
    {
        $lineItem = $doc->createElement('ram:IncludedSupplyChainTradeLineItem');

        $associatedDocument = $this->appendSimpleElement($doc, $lineItem, 'ram:AssociatedDocumentLineDocument');
        $this->appendSimpleElement($doc, $associatedDocument, 'ram:LineID', $data['AssociatedDocumentLineDocument']['LineID'] ?? '');

        $specifiedProduct = $this->appendSimpleElement($doc, $lineItem, 'ram:SpecifiedTradeProduct');
        if ($profile === 'EN16931') {
            $this->appendSimpleElement($doc, $specifiedProduct, 'ram:SellerAssignedID', $data['SpecifiedTradeProduct']['SellerAssignedID'] ?? '');
        }
        $this->appendSimpleElement($doc, $specifiedProduct, 'ram:Name', $data['SpecifiedTradeProduct']['Name'] ?? '');

        $lineAgreement = $this->appendSimpleElement($doc, $lineItem, 'ram:SpecifiedLineTradeAgreement');
        $netPrice = $this->appendSimpleElement($doc, $lineAgreement, 'ram:NetPriceProductTradePrice');
        $this->appendSimpleElement($doc, $netPrice, 'ram:ChargeAmount', (string)($data['SpecifiedLineTradeAgreement']['NetPriceProductTradePrice']['ChargeAmount'] ?? 0));

        $lineDelivery = $this->appendSimpleElement($doc, $lineItem, 'ram:SpecifiedLineTradeDelivery');
        $billedQuantity = $this->appendSimpleElement($doc, $lineDelivery, 'ram:BilledQuantity', (string)($data['SpecifiedLineTradeDelivery']['BilledQuantity'] ?? 0));
        $billedQuantity->setAttribute('unitCode', 'C62'); // C62 is for "one"

        $lineSettlement = $this->appendSimpleElement($doc, $lineItem, 'ram:SpecifiedLineTradeSettlement');
        $lineSettlement->appendChild($this->createApplicableTradeTax($doc, $data['SpecifiedLineTradeSettlement']['ApplicableTradeTax'] ?? []));
        $lineMonetarySummation = $this->appendSimpleElement($doc, $lineSettlement, 'ram:SpecifiedTradeSettlementLineMonetarySummation');
        $this->appendSimpleElement($doc, $lineMonetarySummation, 'ram:LineTotalAmount', (string)($data['SpecifiedLineTradeSettlement']['SpecifiedTradeSettlementLineMonetarySummation']['LineTotalAmount'] ?? 0));

        return $lineItem;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createApplicableHeaderTradeAgreement(DOMDocument $doc, array $data, string $profile): DOMElement
    {
        $tradeAgreement = $doc->createElement('ram:ApplicableHeaderTradeAgreement');
        $tradeAgreement->appendChild($this->createTradeParty($doc, 'SellerTradeParty', $data['SellerTradeParty'] ?? [], $profile));
        $tradeAgreement->appendChild($this->createTradeParty($doc, 'BuyerTradeParty', $data['BuyerTradeParty'] ?? [], $profile));
        return $tradeAgreement;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createTradeParty(DOMDocument $doc, string $partyTag, array $data, string $profile): DOMElement
    {
        $party = $doc->createElement("ram:$partyTag");

        $this->appendSimpleElement($doc, $party, 'ram:Name', $data['Name'] ?? 'Unknown');

        if ($profile === 'EN16931' && !empty($data['DefinedTradeContact'])) {
            $party->appendChild($this->createDefinedTradeContact($doc, $data['DefinedTradeContact']));
        }

        if (!empty($data['PostalTradeAddress'])) {
            $address = $this->appendSimpleElement($doc, $party, 'ram:PostalTradeAddress');
            foreach ($data['PostalTradeAddress'] as $key => $value) {
                $this->appendSimpleElement($doc, $address, "ram:$key", $value);
            }
        }

        if (!empty($data['SpecifiedTaxRegistration']['ID'])) {
            $taxId = $this->appendSimpleElement($doc, $party, 'ram:SpecifiedTaxRegistration');
            $id = $this->appendSimpleElement($doc, $taxId, 'ram:ID', $data['SpecifiedTaxRegistration']['ID']);
            $id->setAttribute('schemeID', 'VA');
        }

        return $party;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createDefinedTradeContact(DOMDocument $doc, array $data): DOMElement
    {
        $contact = $doc->createElement('ram:DefinedTradeContact');
        $this->appendSimpleElement($doc, $contact, 'ram:PersonName', $data['PersonName'] ?? '');
        $this->appendSimpleElement($doc, $contact, 'ram:DepartmentName', $data['DepartmentName'] ?? '');
        $tel = $this->appendSimpleElement($doc, $contact, 'ram:TelephoneUniversalCommunication');
        $this->appendSimpleElement($doc, $tel, 'ram:CompleteNumber', $data['TelephoneUniversalCommunication']['CompleteNumber'] ?? '');
        $email = $this->appendSimpleElement($doc, $contact, 'ram:EmailURIUniversalCommunication');
        $this->appendSimpleElement($doc, $email, 'ram:URIID', $data['EmailURIUniversalCommunication']['URIID'] ?? '');
        return $contact;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createApplicableHeaderTradeSettlement(DOMDocument $doc, array $data, string $profile): DOMElement
    {
        $tradeSettlement = $doc->createElement('ram:ApplicableHeaderTradeSettlement');
        $this->appendSimpleElement($doc, $tradeSettlement, 'ram:InvoiceCurrencyCode', $data['InvoiceCurrencyCode'] ?? '');

        if ($profile !== 'MINIMUM') {
            if ($profile === 'EN16931' && !empty($data['SpecifiedTradeSettlementPaymentMeans'])) {
                $tradeSettlement->appendChild($this->createSpecifiedTradeSettlementPaymentMeans($doc, $data['SpecifiedTradeSettlementPaymentMeans']));
            }

            foreach ($data['ApplicableTradeTax'] ?? [] as $taxData) {
                $tradeSettlement->appendChild($this->createApplicableTradeTax($doc, $taxData));
            }

            if (!empty($data['SpecifiedTradePaymentTerms'])) {
                $tradeSettlement->appendChild($this->createSpecifiedTradePaymentTerms($doc, $data['SpecifiedTradePaymentTerms']));
            }

            if ($profile === 'EN16931') {
                foreach ($data['SpecifiedTradeAllowanceCharge'] ?? [] as $allowanceChargeData) {
                    $tradeSettlement->appendChild($this->createSpecifiedTradeAllowanceCharge($doc, $allowanceChargeData));
                }
            }
        }

        $monetarySummation = $this->createSpecifiedTradeSettlementHeaderMonetarySummation($doc, $data['SpecifiedTradeSettlementHeaderMonetarySummation'] ?? [], $profile);
        $tradeSettlement->appendChild($monetarySummation);
        return $tradeSettlement;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createSpecifiedTradeSettlementPaymentMeans(DOMDocument $doc, array $data): DOMElement
    {
        $paymentMeans = $doc->createElement('ram:SpecifiedTradeSettlementPaymentMeans');
        $this->appendSimpleElement($doc, $paymentMeans, 'ram:TypeCode', $data['TypeCode'] ?? '');
        $payeeAccount = $this->appendSimpleElement($doc, $paymentMeans, 'ram:PayeePartyCreditorFinancialAccount');
        $this->appendSimpleElement($doc, $payeeAccount, 'ram:IBANID', $data['PayeePartyCreditorFinancialAccount']['IBANID'] ?? '');
        $payeeInstitution = $this->appendSimpleElement($doc, $paymentMeans, 'ram:PayeeSpecifiedCreditorFinancialInstitution');
        $this->appendSimpleElement($doc, $payeeInstitution, 'ram:BICID', $data['PayeeSpecifiedCreditorFinancialInstitution']['BICID'] ?? '');
        return $paymentMeans;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createSpecifiedTradeAllowanceCharge(DOMDocument $doc, array $data): DOMElement
    {
        $allowanceCharge = $doc->createElement('ram:SpecifiedTradeAllowanceCharge');
        $chargeIndicator = $this->appendSimpleElement($doc, $allowanceCharge, 'ram:ChargeIndicator');
        $chargeIndicator->appendChild($doc->createElement('udt:Indicator', $data['ChargeIndicator'] ? 'true' : 'false'));
        $this->appendSimpleElement($doc, $allowanceCharge, 'ram:ActualAmount', (string)($data['ActualAmount'] ?? 0));
        $this->appendSimpleElement($doc, $allowanceCharge, 'ram:Reason', $data['Reason'] ?? '');
        return $allowanceCharge;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createSpecifiedTradePaymentTerms(DOMDocument $doc, array $data): DOMElement
    {
        $paymentTerms = $doc->createElement('ram:SpecifiedTradePaymentTerms');
        $this->appendSimpleElement($doc, $paymentTerms, 'ram:Description', $data['Description'] ?? '');
        $dueDate = $this->appendSimpleElement($doc, $paymentTerms, 'ram:DueDateDateTime');
        $dateTimeString = $this->appendSimpleElement($doc, $dueDate, 'udt:DateTimeString', $data['DueDateDateTime'] ?? '');
        $dateTimeString->setAttribute('format', '102');
        return $paymentTerms;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createApplicableTradeTax(DOMDocument $doc, array $data): DOMElement
    {
        $tradeTax = $doc->createElement('ram:ApplicableTradeTax');
        if (isset($data['CalculatedAmount'])) {
            $this->appendSimpleElement($doc, $tradeTax, 'ram:CalculatedAmount', (string)($data['CalculatedAmount'] ?? 0));
        }
        $this->appendSimpleElement($doc, $tradeTax, 'ram:TypeCode', $data['TypeCode'] ?? '');
        if (isset($data['BasisAmount'])) {
            $this->appendSimpleElement($doc, $tradeTax, 'ram:BasisAmount', (string)($data['BasisAmount'] ?? 0));
        }
        $this->appendSimpleElement($doc, $tradeTax, 'ram:CategoryCode', $data['CategoryCode'] ?? '');
        $this->appendSimpleElement($doc, $tradeTax, 'ram:RateApplicablePercent', (string)($data['RateApplicablePercent'] ?? 0));
        return $tradeTax;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createSpecifiedTradeSettlementHeaderMonetarySummation(DOMDocument $doc, array $data, string $profile): DOMElement
    {
        $monetarySummation = $doc->createElement('ram:SpecifiedTradeSettlementHeaderMonetarySummation');

        if ($profile !== 'MINIMUM') {
            $this->appendSimpleElement($doc, $monetarySummation, 'ram:LineTotalAmount', (string) ($data['LineTotalAmount'] ?? 0));
        }

        if ($profile === 'EN16931') {
            $this->appendSimpleElement($doc, $monetarySummation, 'ram:ChargeTotalAmount', (string) ($data['ChargeTotalAmount'] ?? 0));
            $this->appendSimpleElement($doc, $monetarySummation, 'ram:AllowanceTotalAmount', (string) ($data['AllowanceTotalAmount'] ?? 0));
        }

        $this->appendSimpleElement($doc, $monetarySummation, 'ram:TaxBasisTotalAmount', (string) ($data['TaxBasisTotalAmount'] ?? 0));
        $this->appendSimpleElement($doc, $monetarySummation, 'ram:TaxTotalAmount', (string) ($data['TaxTotalAmount'] ?? 0));
        $this->appendSimpleElement($doc, $monetarySummation, 'ram:GrandTotalAmount', (string) ($data['GrandTotalAmount'] ?? 0));
        $this->appendSimpleElement($doc, $monetarySummation, 'ram:DuePayableAmount', (string) ($data['DuePayableAmount'] ?? 0));
        return $monetarySummation;
    }

    private function appendSimpleElement(DOMDocument $doc, DOMElement $parent, string $name, ?string $value = null): DOMElement
    {
        $element = $doc->createElement($name, $value ?? '');
        $parent->appendChild($element);
        return $element;
    }

    private function profileUri(string $profile): string
    {
        return match (strtoupper($profile)) {
            'MINIMUM'   => 'urn:factur-x.eu:1p0:minimum',
            'BASICWL'  => 'urn:factur-x.eu:1p0:basicwl',
            'BASIC'     => 'urn:factur-x.eu:1p0:basic',
            'EN16931'   => 'urn:factur-x.eu:1p0:en16931',
            default     => 'urn:factur-x.eu:1p0:basic',
        };
    }
}