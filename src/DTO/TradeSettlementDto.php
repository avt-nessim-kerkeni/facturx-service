<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class TradeSettlementDto
{
    #[Assert\NotBlank]
    public ?string $InvoiceCurrencyCode = null;

    #[Assert\Valid]
    public ?PaymentTermsDto $SpecifiedTradePaymentTerms = null;

    /**
     * @var TradeTaxDto[]
     */
    #[Assert\Valid]
    public array $ApplicableTradeTax = [];

    /**
     * @var AllowanceChargeDto[]
     */
    #[Assert\Valid]
    public array $SpecifiedTradeAllowanceCharge = [];

    #[Assert\Valid]
    public ?MonetarySummationDto $SpecifiedTradeSettlementHeaderMonetarySummation = null;

    #[Assert\Valid]
    public ?PaymentMeansDto $SpecifiedTradeSettlementPaymentMeans = null;
}
