<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class TradeDeliveryDto
{
    #[Assert\NotBlank]
    public ?float $BilledQuantity = null;
}
