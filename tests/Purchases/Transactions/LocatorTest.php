<?php
namespace PHPSC\PagSeguro\Purchases\Transactions;

use PHPSC\PagSeguro\Credentials;
use PHPSC\PagSeguro\Client\Client;
use PHPSC\PagSeguro\Environment;
use DateTime;

/**
 * @author Luís Otávio Cobucci Oblonczyk <lcobucci@gmail.com>
 */
class LocatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Credentials
     */
    protected $credentials;

    /**
     * @var Client|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $client;

    /**
     * @var Decoder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $decoder;

    /**
     * @var Transaction|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transaction;

    protected function setUp()
    {
        $environment = $this->getMockForAbstractClass(Environment::class);

        $environment->expects($this->any())
                    ->method('getHost')
                    ->willReturn('test.com');

        $environment->expects($this->any())
                    ->method('getWsHost')
                    ->willReturn('ws.test.com');

        $this->credentials = new Credentials('a@a.com', 't', $environment);
        $this->client = $this->getMock(Client::class, [], [], '', false);

        $this->decoder = $this->getMock(
            Decoder::class,
            [],
            [],
            '',
            false
        );

        $this->transaction = $this->getMock(
            Transaction::class,
            [],
            [],
            '',
            false
        );
    }

    /**
     * @test
     */
    public function getByCodeShouldDoAGetRequestAddingCredentialsData()
    {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><data />');

        $this->client->expects($this->once())
                     ->method('get')
                     ->with('https://ws.test.com/v2/transactions/1?email=a%40a.com&token=t')
                     ->willReturn($xml);

        $this->decoder->expects($this->once())
                      ->method('decode')
                      ->with($xml)
                      ->willReturn($this->transaction);

        $service = new Locator($this->credentials, $this->client, $this->decoder);

        $this->assertSame($this->transaction, $service->getByCode(1));
    }

    /**
     * @test
     */
    public function getByNotificationShouldDoAGetRequestAddingCredentialsData()
    {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><data />');

        $this->client->expects($this->once())
                     ->method('get')
                     ->with('https://ws.test.com/v2/transactions/notifications/1?email=a%40a.com&token=t')
                     ->willReturn($xml);

        $this->decoder->expects($this->once())
                      ->method('decode')
                      ->with($xml)
                      ->willReturn($this->transaction);

        $service = new Locator($this->credentials, $this->client, $this->decoder);

        $this->assertSame($this->transaction, $service->getByNotification(1));
    }
    
    /**
     * @test
     */
    public function getByPeriodShouldDoAGetRequestAddingCredentialsData()
    {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><data />');

        $initialDate = new DateTime('2015-01-01');
        $finalDate = new DateTime('2015-01-10');
        $page = 1;
        $maxPageResults = 50;

        $this->client->expects($this->once())
                ->method('get')
                ->with(
                    'https://ws.test.com/v2/transactions/?' .
                    http_build_query([
                        'initialDate' => $initialDate->format('Y-m-d\TH:i'),
                        'finalDate' => $finalDate->format('Y-m-d\TH:i'),
                        'page' => $page,
                        'maxPageResults' => $maxPageResults
                     ]) .
                    '&email=a%40a.com&token=t'
                )
                ->willReturn($xml);
        $transactionSearchResult = $this->getMock(
            TransactionSearchResult::class,
            [],
            [],
            '',
            false
        );
        $this->decoder->expects($this->once())
                ->method('decodeTransactionSearch')
                ->with($xml)
                ->willReturn($transactionSearchResult);

        $service = new Locator($this->credentials, $this->client, $this->decoder);

        $this->assertSame(
            $transactionSearchResult,
            $service->getByPeriod($initialDate, $finalDate, $page, $maxPageResults)
        );
    }
}
