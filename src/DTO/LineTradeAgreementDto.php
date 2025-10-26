<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class LineTradeAgreementDto
{
    #[Assert\Valid]
    public ?ProductTradePriceDto $NetPriceProductTradePrice = null;
}
