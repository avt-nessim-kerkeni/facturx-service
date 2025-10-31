<?php

namespace App\Service;

class FacturxJsonService
{
    /**
     * @param \App\Entity\Mouvement $mouvement
     */
    public function generate(object $mouvement, string $profile = 'MINIMUM'): array
    {
        $profile = strtoupper($profile);

        return match ($profile) {
            'MINIMUM'    => $this->buildMinimum($mouvement),
            'BASICWL'    => $this->buildBasic($mouvement, 'BASICWL'),
            'BASIC'      => $this->buildBasic($mouvement, 'BASIC'),
            'EN16931'    => $this->buildEn16931($mouvement),
            default      => throw new \InvalidArgumentException("Unknown Factur-X profile: $profile")
        };
    }

    /**
     * @return array<string,array>
     */
    private function buildMinimum(object $mouvement): array
    {
        $invoiceData = [];
        $invoiceData['ExchangedDocument'] = $this->createExchangedDocument($mouvement);
        $invoiceData['SupplyChainTradeTransaction'] = $this->createSupplyChainTradeTransaction($mouvement, 'MINIMUM');
        return $invoiceData;
    }

    /**
     * @return array<string,array>
     */
    private function buildBasic(object $mouvement, string $profile): array
    {
        $invoiceData = [];
        $invoiceData['ExchangedDocument'] = $this->createExchangedDocument($mouvement);
        $invoiceData['SupplyChainTradeTransaction'] = $this->createSupplyChainTradeTransaction($mouvement, $profile);
        return $invoiceData;
    }

    /**
     * @return array<string,array>
     */
    private function buildEn16931(object $mouvement): array
    {
        $invoiceData = [];
        $invoiceData['ExchangedDocument'] = $this->createExchangedDocument($mouvement);
        $invoiceData['SupplyChainTradeTransaction'] = $this->createSupplyChainTradeTransaction($mouvement, 'EN16931');
        return $invoiceData;
    }

    /**
     * @return array<string,mixed>
     */
    private function createExchangedDocument(object $mouvement): array
    {
        return [
            'ID' => $mouvement->getNumeroMouvement(),
            'TypeCode' => '380',
            'IssueDateTime' => $mouvement->getDateMouvement()->format('Ymd'),
        ];
    }

    /**
     * @return array<string,array>
     */
    private function createSupplyChainTradeTransaction(object $mouvement, string $profile): array
    {
        $transaction = [];

        if ($profile === 'BASIC' || $profile === 'EN16931') {
            $transaction['IncludedSupplyChainTradeLineItem'] = $this->createIncludedSupplyChainTradeLineItems($mouvement, $profile);
        }

        $transaction['ApplicableHeaderTradeAgreement'] = $this->createApplicableHeaderTradeAgreement($mouvement, $profile);
        $transaction['ApplicableHeaderTradeSettlement'] = $this->createApplicableHeaderTradeSettlement($mouvement, $profile);

        return $transaction;
    }

    private function createIncludedSupplyChainTradeLineItems(object $mouvement, string $profile): array
    {
        $lines = [];
        // foreach ($mouvement->getMouvementLignes() as $ligne) {
        //     $lines[] = $this->createIncludedSupplyChainTradeLineItem($ligne, $profile);
        // }
        return $lines;
    }

    /**
     * @return array<string,mixed>
     */
    private function createIncludedSupplyChainTradeLineItem(object $ligne, string $profile): array
    {
        $lineItem = [
            'AssociatedDocumentLineDocument' => [
                'LineID' => '1' // $ligne->getId(),
            ],
            'SpecifiedTradeProduct' => [
                'Name' => 'Product A' // $ligne->getProduit()->getLibelle(),
            ],
            'SpecifiedLineTradeAgreement' => [
                'NetPriceProductTradePrice' => [
                    'ChargeAmount' => 100.00 // $ligne->getPrixUnitaire(),
                ]
            ],
            'SpecifiedLineTradeDelivery' => [
                'BilledQuantity' => 1.0 // $ligne->getQuantite(),
            ],
            'SpecifiedLineTradeSettlement' => [
                'ApplicableTradeTax' => [
                    'TypeCode' => 'VAT',
                    'CategoryCode' => 'S',
                    'RateApplicablePercent' => 20.00 // $ligne->getTva()->getTaux(),
                ],
                'SpecifiedTradeSettlementLineMonetarySummation' => [
                    'LineTotalAmount' => 100.00 // $ligne->getMontantTotal(),
                ]
            ]
        ];

        if ($profile === 'EN16931') {
            // Add EN16931 specific fields
        }

        return $lineItem;
    }

    /**
     * @return array<string,array>
     */
    private function createApplicableHeaderTradeAgreement(object $mouvement, string $profile): array
    {
        return [
            'SellerTradeParty' => $this->createTradeParty($mouvement, 'seller', $profile),
            'BuyerTradeParty' => $this->createTradeParty($mouvement, 'buyer', $profile),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function createTradeParty(object $mouvement, string $partyType, string $profile): array
    {
        $party = [];
        if ($partyType === 'seller') {
            // $filiale = $mouvement->getTypeMouvement()->getFiliale();
            $party = [
                'Name' => 'Seller Name', // $filiale->getNom(),
                'PostalTradeAddress' => [
                    'PostcodeCode' => '75001', // $filiale->getAdresse()->getCode(),
                    'LineOne' => '1 Rue de la Paix', // $filiale->getAdresse()->getNumeroRue() . ' ' . $filiale->getAdresse()->getRue(),
                    'CityName' => 'Paris', // $filiale->getAdresse()->getVille(),
                    'CountryID' => 'FR', // $filiale->getAdresse()->getPays(),
                ],
                'SpecifiedTaxRegistration' => [
                    'ID' => 'FR123456789', // $filiale->getNumeroTva(),
                ],
            ];
        } else { // buyer
            // $client = $mouvement->getClient();
            $party = [
                'Name' => 'Buyer Name', // $client->getNomPublic(),
                'PostalTradeAddress' => [
                    'CountryID' => 'DE', // $client->getAdresse()->getPays(),
                ],
            ];
        }

        return $party;
    }

    /**
     * @return array<string,mixed>
     */
    private function createApplicableHeaderTradeSettlement(object $mouvement, string $profile): array
    {
        $settlement = [
            'InvoiceCurrencyCode' => 'EUR',
            'SpecifiedTradePaymentTerms' => [
                'Description' => 'Payment by bank transfer', // $mouvement->getModePaiement() ? $mouvement->getModePaiement()->getLibelle() : '',
                'DueDateDateTime' => (new \DateTime())->modify('+30 days')->format('Ymd'), // Calculate due date
            ],
            'ApplicableTradeTax' => [], // Calculated from lines
            'SpecifiedTradeSettlementHeaderMonetarySummation' => [
                'LineTotalAmount' => 100.00, // Calculated from lines
                'TaxBasisTotalAmount' => 100.00, // Calculated from lines
                'TaxTotalAmount' => 20.00, // Calculated from lines
                'GrandTotalAmount' => 120.00, // Calculated from lines
                'DuePayableAmount' => 120.00, // Calculated from lines
            ],
        ];

        return $settlement;
    }
}
