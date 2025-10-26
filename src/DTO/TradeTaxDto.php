<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class TradeTaxDto
{
    public ?float $CalculatedAmount = null;
    #[Assert\NotBlank]
    public ?string $TypeCode = null;
    public ?float $BasisAmount = null;
    #[Assert\NotBlank]
    public ?string $CategoryCode = null;
    public ?float $RateApplicablePercent = null;
}
