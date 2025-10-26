<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class PaymentMeansDto
{
    #[Assert\NotBlank]
    public ?string $TypeCode = null;
    #[Assert\Valid]
    public ?CreditorFinancialAccountDto $PayeePartyCreditorFinancialAccount = null;
    #[Assert\Valid]
    public ?CreditorFinancialInstitutionDto $PayeeSpecifiedCreditorFinancialInstitution = null;
}
