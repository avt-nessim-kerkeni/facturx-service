<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class TaxRegistrationDto
{
    #[Assert\NotBlank]
    public ?string $ID = null;
}
