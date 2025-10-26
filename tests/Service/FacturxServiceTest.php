<?php

namespace App\Tests\Service;

use App\Service\FacturxService;
use App\Service\FacturxXmlService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FacturxService::class)]
class FacturxServiceTest extends KernelTestCase
{
    private FacturxService $facturxService;
    private string $fixturesPath;
    private string $outputPath;

    protected function setUp(): void
    {
        parent::setUp();

        $xmlService = new FacturxXmlService();
        $this->facturxService = new FacturxService($xmlService);
        $this->fixturesPath = __DIR__ . '/../fixtures';
        $this->outputPath = __DIR__ . '/../output';

        if (!is_dir($this->outputPath)) {
            mkdir($this->outputPath, 0777, true);
        }
    }

    public static function profileProvider(): array
    {
        return [
            ['MINIMUM', 'facturx_minimum.json'],
            ['BASICWL', 'facturx_basic-wl.json'],
            ['BASIC', 'facturx_basic.json'],
            ['EN16931', 'facturx_en-16931.json'],
        ];
    }

    #[DataProvider('profileProvider')]
    public function testGeneratePdf(string $profile, string $fixtureFile): string
    {
        $sourcePdf = $this->fixturesPath . '/invoice.pdf';
        $outputPdf = $this->outputPath . '/facturx_invoice_' . strtolower($profile) . '.pdf';
        $invoiceData = json_decode(file_get_contents($this->fixturesPath . '/' . $fixtureFile), true);

        $this->assertFileExists($sourcePdf, 'Source PDF fixture not found. Please provide a tests/fixtures/invoice.pdf file.');

        $generatedPdfPath = $this->facturxService->generatePdf($sourcePdf, $invoiceData, $profile, $outputPdf);

        $this->assertFileExists($generatedPdfPath);
        $this->assertSame($outputPdf, $generatedPdfPath);

        return $generatedPdfPath;
    }

    #[DataProvider('profileProvider')]
    public function testReadAndValidatePdfValid(string $profile, string $fixtureFile): void
    {
        $sourcePdf = $this->fixturesPath . '/invoice.pdf';
        $outputPdf = $this->outputPath . '/facturx_invoice_' . strtolower($profile) . '.pdf';
        $invoiceData = json_decode(file_get_contents($this->fixturesPath . '/' . $fixtureFile), true);
        $this->facturxService->generatePdf($sourcePdf, $invoiceData, $profile, $outputPdf);

        $result = $this->facturxService->readAndValidatePdf($outputPdf);

        $this->assertTrue($result['valid']);
        $this->assertNull($result['xsd_errors']);
        $this->assertSame($profile, $result['profile']);
    }

    public function testReadAndValidatePdfInvalid(): void
    {
        $invalidPdf = $this->fixturesPath . '/regular.pdf';
        $this->assertFileExists($invalidPdf, 'Regular PDF fixture not found. Please provide a tests/fixtures/regular.pdf file.');

        $result = $this->facturxService->readAndValidatePdf($invalidPdf);

        $this->assertFalse($result['valid']);
    }


}