<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class TradeAgreementDto
{
    #[Assert\Valid]
    public ?TradePartyDto $SellerTradeParty = null;

    #[Assert\Valid]
    public ?TradePartyDto $BuyerTradeParty = null;
}
