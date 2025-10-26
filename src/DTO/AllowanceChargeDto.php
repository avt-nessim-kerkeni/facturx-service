<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class AllowanceChargeDto
{
    #[Assert\NotNull]
    public ?bool $ChargeIndicator = null;
    #[Assert\NotBlank]
    public ?float $ActualAmount = null;
    public ?string $Reason = null;
}
