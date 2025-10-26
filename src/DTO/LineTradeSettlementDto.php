<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class LineTradeSettlementDto
{
    #[Assert\Valid]
    public ?TradeTaxDto $ApplicableTradeTax = null;

    #[Assert\Valid]
    public ?LineMonetarySummationDto $SpecifiedTradeSettlementLineMonetarySummation = null;
}
