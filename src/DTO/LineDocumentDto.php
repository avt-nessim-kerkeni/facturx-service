<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class LineDocumentDto
{
    #[Assert\NotBlank]
    public ?string $LineID = null;
}
