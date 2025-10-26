<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class TradeContactDto
{
    public ?string $PersonName = null;
    public ?string $DepartmentName = null;
    #[Assert\Valid]
    public ?UniversalCommunicationDto $TelephoneUniversalCommunication = null;
    #[Assert\Valid]
    public ?UniversalCommunicationDto $EmailURIUniversalCommunication = null;
}
