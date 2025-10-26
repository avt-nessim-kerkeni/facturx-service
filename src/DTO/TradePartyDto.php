<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class TradePartyDto
{
    #[Assert\NotBlank]
    public ?string $Name = null;

    #[Assert\Valid]
    public ?TradeAddressDto $PostalTradeAddress = null;

    #[Assert\Valid]
    public ?TaxRegistrationDto $SpecifiedTaxRegistration = null;

    #[Assert\Valid]
    public ?TradeContactDto $DefinedTradeContact = null;
}
