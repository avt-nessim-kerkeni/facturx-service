<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class SupplyChainTradeTransactionDto
{
    /**
     * @var SupplyChainTradeLineItemDto[]
     */
    #[Assert\Valid]
    public array $IncludedSupplyChainTradeLineItem = [];

    #[Assert\Valid]
    public ?TradeAgreementDto $ApplicableHeaderTradeAgreement = null;

    #[Assert\Valid]
    public ?TradeSettlementDto $ApplicableHeaderTradeSettlement = null;
}
