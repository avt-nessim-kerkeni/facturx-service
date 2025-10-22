<?php

namespace App\Tests\Service;

use App\Service\FacturxXmlService;
use Atgp\FacturX\XsdValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FacturxXmlServiceTest extends KernelTestCase
{
    private FacturxXmlService $xmlService;
    private XsdValidator $xmlValidator;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->xmlService = new FacturxXmlService();
        $this->xmlValidator = new XsdValidator();
    }

    #[DataProvider('profileProvider')]
    public function testXmlGenerationFromJsonFixture(string $profile, string $fixtureFile): void
    {
        $jsonPath = __DIR__ . '/../fixtures/' . $fixtureFile;

        $this->assertFileExists($jsonPath, "Fixture file not found: $fixtureFile");

        $invoiceData = json_decode(file_get_contents($jsonPath), true);
        $this->assertIsArray($invoiceData, 'Invoice data should be an array');

        $xml = $this->xmlService->generate($invoiceData, $profile);
        $isValid = $this->xmlValidator->validate($xml);

        $this->assertTrue($isValid, "Generated XML is invalid for profile: $profile");
    }

    /**
     * Provides different profiles and fixture files
     */
    public static function profileProvider(): array
    {
        return [
            ['minimum', 'facturx_minimum.json'],
            ['basic-wl', 'facturx_basic-wl.json'],
            ['basic', 'facturx_basic.json'],
            ['en16931', 'facturx_en-16931.json'],
        ];
    }
}
