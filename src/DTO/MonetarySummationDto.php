<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class MonetarySummationDto
{
    public ?float $LineTotalAmount = null;
    public ?float $ChargeTotalAmount = null;
    public ?float $AllowanceTotalAmount = null;
    #[Assert\NotBlank]
    public ?float $TaxBasisTotalAmount = null;
    #[Assert\NotBlank]
    public ?float $TaxTotalAmount = null;
    #[Assert\NotBlank]
    public ?float $GrandTotalAmount = null;
    #[Assert\NotBlank]
    public ?float $DuePayableAmount = null;
}
