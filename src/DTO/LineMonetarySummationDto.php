<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class LineMonetarySummationDto
{
    #[Assert\NotBlank]
    public ?float $LineTotalAmount = null;
}
