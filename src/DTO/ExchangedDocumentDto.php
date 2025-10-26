<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class ExchangedDocumentDto
{
    #[Assert\NotBlank]
    public ?string $ID = null;

    #[Assert\NotBlank]
    public ?string $TypeCode = null;

    #[Assert\NotBlank]
    public ?string $IssueDateTime = null;
}
