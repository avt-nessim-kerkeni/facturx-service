<?php

namespace App\Tests\Service;

use App\Service\FacturxJsonService;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FacturxJsonServiceTest extends KernelTestCase
{
    private FacturxJsonService $facturxJsonService;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->facturxJsonService = new FacturxJsonService();
    }

    #[DataProvider('profileProvider')]
    public function testJsonGeneration(string $profile, string $fixtureFile): void
    {
        $jsonPath = __DIR__ . '/../fixtures/' . $fixtureFile;
        $this->assertFileExists($jsonPath, "Fixture file not found: $fixtureFile");

        $expectedInvoiceData = json_decode(file_get_contents($jsonPath), true);
        $this->assertIsArray($expectedInvoiceData, 'Invoice data should be an array');

        // Create a mock Mouvement object based on the fixture data
        $mouvement = $this->createMouvementMock($expectedInvoiceData);

        $generatedInvoiceData = $this->facturxJsonService->generate($mouvement, $profile);

        $this->assertEquals($expectedInvoiceData, $generatedInvoiceData);
    }

    private function createMouvementMock(array $invoiceData): object
    {
        $mouvement = $this->createMock(\stdClass::class);
        $mouvement->method('getNumeroMouvement')->willReturn($invoiceData['ExchangedDocument']['ID']);
        $mouvement->method('getDateMouvement')->willReturn(new \DateTime($invoiceData['ExchangedDocument']['IssueDateTime']));

        $typeMouvement = $this->createMock(\stdClass::class);
        $filiale = $this->createMock(\stdClass::class);
        $filialeAdresse = $this->createMock(\stdClass::class);

        $sellerParty = $invoiceData['SupplyChainTradeTransaction']['ApplicableHeaderTradeAgreement']['SellerTradeParty'];
        $filiale->method('getNom')->willReturn($sellerParty['Name']);
        $filialeAdresse->method('getPays')->willReturn($sellerParty['PostalTradeAddress']['CountryID']);
        $filiale->method('getAdresse')->willReturn($filialeAdresse);
        $filiale->method('getNumeroTva')->willReturn($sellerParty['SpecifiedTaxRegistration']['ID']);
        $typeMouvement->method('getFiliale')->willReturn($filiale);
        $mouvement->method('getTypeMouvement')->willReturn($typeMouvement);

        $client = $this->createMock(\stdClass::class);
        $clientAdresse = $this->createMock(\stdClass::class);
        $buyerParty = $invoiceData['SupplyChainTradeTransaction']['ApplicableHeaderTradeAgreement']['BuyerTradeParty'];
        $client->method('getNomPublic')->willReturn($buyerParty['Name']);
        $clientAdresse->method('getPays')->willReturn($buyerParty['PostalTradeAddress']['CountryID']);
        $client->method('getAdresse')->willReturn($clientAdresse);
        $mouvement->method('getClient')->willReturn($client);
        
        $mouvement->method('getModePaiement')->willReturn(null);

        return $mouvement;
    }

    public static function profileProvider(): array
    {
        return [
            ['minimum', 'facturx_minimum.json'],
            // ['basicwl', 'facturx_basic-wl.json'],
            // ['basic', 'facturx_basic.json'],
            // ['en16931', 'facturx_en-16931.json'],
        ];
    }
}
