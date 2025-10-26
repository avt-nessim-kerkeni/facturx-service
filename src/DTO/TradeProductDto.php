<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class TradeProductDto
{
    public ?string $SellerAssignedID = null;
    #[Assert\NotBlank]
    public ?string $Name = null;
}
