<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class TradeAddressDto
{
    public ?string $PostcodeCode = null;
    public ?string $LineOne = null;
    public ?string $CityName = null;

    #[Assert\NotBlank]
    public ?string $CountryID = null;
}
