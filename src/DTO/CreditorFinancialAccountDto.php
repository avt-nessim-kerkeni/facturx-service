<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class CreditorFinancialAccountDto
{
    #[Assert\NotBlank]
    public ?string $IBANID = null;
}
