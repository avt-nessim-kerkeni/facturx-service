<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreditorFinancialInstitutionDto
{
    #[Assert\NotBlank]
    public ?string $BICID = null;
}
