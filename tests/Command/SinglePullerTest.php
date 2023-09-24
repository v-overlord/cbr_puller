<?php

namespace Command;

use App\AppKernel;
use CbrPuller\ADT\Price;
use CbrPuller\Service\FetcherRate;
use CbrPuller\VO\PairHistory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class SinglePullerTest extends TestCase
{
    private ?MockObject $fetcherRateServiceMock;

    private ?CommandTester $commandTester;

    private ContainerInterface $container;

    private string $baseCurrency = 'RUR';
    private string $counterCurrency = 'USD';

    private float $priceValue = 42;

    private ?\DateTimeImmutable $dateTime = null;

    protected function setUp(): void
    {
        $this->dateTime = new \DateTimeImmutable('2020-01-01');

        $kernel = new AppKernel('test', false);
        $kernel->boot();

        $container = $kernel->getContainer();
        $container->set('di', $container);

        $this->container = $container;

        $this->fetcherRateServiceMock = $this->getMockBuilder(FetcherRate::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->fetcherRateServiceMock
            ->expects($this->exactly(1))
            ->method('enrich')
            ->willReturn(new PairHistory($this->baseCurrency, $this->counterCurrency, [
                new Price($this->priceValue, $this->dateTime),
                new Price($this->priceValue - 1, $this->dateTime->sub(new \DateInterval('P1D')))
            ]));

        $this->container->set(FetcherRate::class, $this->fetcherRateServiceMock);

        $application = $container->get(Application::class);

        $command = $application->find('pull');

        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $this->fetcherRateServiceMock = null;
        $this->commandTester = null;
    }

    public function testExecuteCli()
    {
        $this->commandTester->execute(['currency' => $this->counterCurrency, 'date' => '2020-01-01', '-b' => $this->baseCurrency, '-d' => 'cli', '-vvv' => '']);

        $this->assertEquals('------------ --------- --------- ---------- --------- ---- --- 
  01/01/2020   RUR/USD   0.02381   -0.00058   USD/RUR   42   1  
  31/12/2019   RUR/USD   0.02439   0          USD/RUR   41   0  
 ------------ --------- --------- ---------- --------- ---- ---', trim($this->commandTester->getDisplay()));
    }

    public function testExecuteJson()
    {
        $this->commandTester->execute(['currency' => $this->counterCurrency, 'date' => '2020-01-01', '-b' => $this->baseCurrency, '-d' => 'json', '-vvv' => '']);

        $this->assertEquals('[
    {
        "date": "01\/01\/2020",
        "pair": "RUR\/USD",
        "rate": 0.02381,
        "rateDifference": -0.00058,
        "swappedPair": "USD\/RUR",
        "swappedPairRate": 42,
        "swappedPairRateDifference": 1
    },
    {
        "date": "31\/12\/2019",
        "pair": "RUR\/USD",
        "rate": 0.02439,
        "rateDifference": 0,
        "swappedPair": "USD\/RUR",
        "swappedPairRate": 41,
        "swappedPairRateDifference": 0
    }
]', trim($this->commandTester->getDisplay()));
    }
}