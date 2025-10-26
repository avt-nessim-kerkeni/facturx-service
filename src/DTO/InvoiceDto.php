<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class InvoiceDto
{
    #[Assert\Valid]
    public ?ExchangedDocumentDto $ExchangedDocument = null;

    #[Assert\Valid]
    public ?SupplyChainTradeTransactionDto $SupplyChainTradeTransaction = null;
}
