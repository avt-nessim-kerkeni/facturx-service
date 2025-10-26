<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class PaymentTermsDto
{
    public ?string $Description = null;
    public ?string $DueDateDateTime = null;
}
