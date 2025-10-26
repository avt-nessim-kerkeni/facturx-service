<?php

namespace App\Tests\DTO;

use App\DTO\InvoiceDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class InvoiceDtoTest extends TestCase
{
    private Serializer $serializer;

    protected function setUp(): void
    {
        $this->serializer = new Serializer(
            [new ObjectNormalizer(), new ArrayDenormalizer()],
            [new JsonEncoder()]
        );
    }

    public function testMinimumInvoiceDtoDeserialization(): void
    {
        $jsonContent = file_get_contents(__DIR__ . '/../fixtures/facturx_minimum.json');
        $data = json_decode($jsonContent, true);

        $invoiceDto = $this->serializer->denormalize($data, InvoiceDto::class);

        $this->assertInstanceOf(InvoiceDto::class, $invoiceDto);
    }
}
