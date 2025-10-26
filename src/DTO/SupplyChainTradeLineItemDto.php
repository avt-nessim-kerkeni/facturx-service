<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class SupplyChainTradeLineItemDto
{
    #[Assert\Valid]
    public ?LineDocumentDto $AssociatedDocumentLineDocument = null;

    #[Assert\Valid]
    public ?TradeProductDto $SpecifiedTradeProduct = null;

    #[Assert\Valid]
    public ?LineTradeAgreementDto $SpecifiedLineTradeAgreement = null;

    #[Assert\Valid]
    public ?TradeDeliveryDto $SpecifiedLineTradeDelivery = null;

    #[Assert\Valid]
    public ?LineTradeSettlementDto $SpecifiedLineTradeSettlement = null;
}
