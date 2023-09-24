<?php

namespace Command;

use App\AppKernel;
use CbrPuller\ADT\Price;
use CbrPuller\Service\FetcherRate;
use CbrPuller\Service\PublishBroker;
use CbrPuller\VO\PairHistory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class PublishTest extends TestCase
{
    private ?MockObject $fetcherRateServiceMock;

    private ?MockObject $publishBrokerMock;

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
            ->willReturn(new PairHistory($this->baseCurrency, $this->counterCurrency, [new Price($this->priceValue, $this->dateTime)]));

        $this->container->set(FetcherRate::class, $this->fetcherRateServiceMock);

        $this->publishBrokerMock = $this->getMockBuilder(PublishBroker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->publishBrokerMock
            ->expects($this->exactly(1))
            ->method('publish')
            ->willReturn(true);

        $this->container->set(PublishBroker::class, $this->publishBrokerMock);

        $application = $container->get(Application::class);

        $command = $application->find('publish');

        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $this->fetcherRateServiceMock = null;
        $this->commandTester = null;
    }

    public function testExecuteOK()
    {
        $this->commandTester->execute(['currency' => $this->counterCurrency, 'date' => '2020-01-01', '-b' => $this->baseCurrency, '-vvv' => '']);

        $this->assertEquals('[OK] Sent', trim($this->commandTester->getDisplay()));
    }
}