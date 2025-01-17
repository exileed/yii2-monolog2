<?php

namespace leinonen\Yii2Monolog\Tests\Integration;

use Yii;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Monolog\Handler\TestHandler;
use leinonen\Yii2Monolog\Yii2Monolog;
use leinonen\Yii2Monolog\Tests\Helpers\ExampleYii2MonologConfiguration;

class Yii2MonologTest extends TestCase
{
    /**
     * @var TestHandler
     */
    private $testHandler;

    /**
     * @var string
     */
    private $channelName;

    public function setUp(): void
    {
        // Configure a test handler which can be accessed in tests.
        // It is used in the example configuration and the component should resolve it through DI.
        $this->testHandler = new TestHandler();
        \Yii::$container->set(TestHandler::class, function () {
            return $this->testHandler;
        });

        $this->channelName = 'myChannel';
        $this->mockApplication([
            'bootstrap' => ['monolog'],
            'components' => [
                'monolog' => [
                    'class' => Yii2Monolog::class,
                    'channels' => [
                        $this->channelName => ExampleYii2MonologConfiguration::getConfiguration(),
                    ],
                ],
            ],
        ]);

        parent::setUp();
    }

    /** @test */
    public function itConfiguresMonologLoggersToBeFetchedFromServiceLocator()
    {
        /** @var Yii2Monolog $component */
        $component = \Yii::$app->monolog;
        $this->assertInstanceOf(Yii2Monolog::class, $component);

        $logger = $component->getLogger($this->channelName);
        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertSame($this->channelName, $logger->getName());
        $this->assertSame([$this->testHandler], $logger->getHandlers());
    }

    /** @test */
    public function loggersAreRegisteredWithAnAliasToTheDiContainer()
    {
        $logger = Yii::$container->get("yii2-monolog.{$this->channelName}");

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertSame($this->channelName, $logger->getName());
        $this->assertSame([$this->testHandler], $logger->getHandlers());

        $this->destroyApplication();

        $otherChannel = 'otherChannel';
        $this->mockApplication([
            'bootstrap' => ['monolog'],
            'components' => [
                'monolog' => [
                    'class' => Yii2Monolog::class,
                    'channels' => [
                        $otherChannel => ExampleYii2MonologConfiguration::getConfiguration(),
                    ],
                ],
            ],
        ]);

        $logger = Yii::$container->get("yii2-monolog.{$otherChannel}");
        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertSame($otherChannel, $logger->getName());
    }

    /** @test */
    public function itCanRegisterMultipleChannels()
    {
        $this->destroyApplication();

        $firstChannel = 'firstChannel';
        $secondChannel = 'secondChannel';
        $this->mockApplication([
            'bootstrap' => ['monolog'],
            'components' => [
                'monolog' => [
                    'class' => Yii2Monolog::class,
                    'channels' => [
                        $firstChannel => [],
                        $secondChannel => [],
                    ],
                ],
            ],
        ]);

        /** @var Yii2Monolog $component */
        $component = \Yii::$app->monolog;

        $logger1 = $component->getLogger($firstChannel);
        $this->assertInstanceOf(Logger::class, $logger1);
        $this->assertSame($firstChannel, $logger1->getName());

        $logger2 = $component->getLogger($secondChannel);
        $this->assertInstanceOf(Logger::class, $logger2);
        $this->assertSame($secondChannel, $logger2->getName());
    }

    /** @test */
    public function itCanRegisterAMainChannelToBeUsedForPsrLoggerInterface()
    {
        $this->destroyApplication();

        $firstChannel = 'firstChannel';
        $secondChannel = 'secondChannel';
        $this->mockApplication([
            'bootstrap' => ['monolog'],
            'components' => [
                'monolog' => [
                    'class' => Yii2Monolog::class,
                    'channels' => [
                        $firstChannel => [],
                        $secondChannel => [],
                    ],
                    'mainChannel' => $secondChannel,
                ],
            ],
        ]);

        $logger = Yii::$container->get(LoggerInterface::class);

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertSame($secondChannel, $logger->getName());
    }

    /** @test */
    public function itRegistersTheFirstChannelImplicitlyToBeUsedForThePsrLoggerInterfaceIfNoMainChannelIsDefined()
    {
        $logger = Yii::$container->get(LoggerInterface::class);

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertSame($this->channelName, $logger->getName());
    }

    /** @test */
    public function itReturnsTheMainLoggerIfNoParametersGivenToGetLoggerMethod()
    {
        $this->destroyApplication();

        $firstChannel = 'firstChannel';
        $secondChannel = 'secondChannel';
        $this->mockApplication([
            'bootstrap' => ['monolog'],
            'components' => [
                'monolog' => [
                    'class' => Yii2Monolog::class,
                    'channels' => [
                        $firstChannel => [],
                        $secondChannel => [],
                    ],
                    'mainChannel' => $secondChannel,
                ],
            ],
        ]);

        $logger = Yii::$app->monolog->getLogger();

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertSame($secondChannel, $logger->getName());
    }

    /** @test */
    public function itConfiguresTheRegisteredLoggersCorrectly()
    {
        /** @var Logger $logger */
        $logger = Yii::$container->get(LoggerInterface::class);

        $logger->warning('my message');
        $logger->error('second message');

        $testMessage1 = $this->testHandler->getRecords()[0];
        $this->assertSame('my message', $testMessage1['message']);
        $this->assertSame($this->channelName, $testMessage1['channel']);
        $this->assertSame('special', $testMessage1['context']['specialValue']);
        $this->assertSame('changed value', $testMessage1['context']['configuredValue']);
        $this->assertStringContainsString('myPrefix', $testMessage1['formatted']);
        $this->assertStringContainsString("{$this->channelName}.WARNING: my message", $testMessage1['formatted']);
        $this->assertStringContainsString('{"test":"testvalue"}', $testMessage1['formatted']);

        $testMessage2 = $this->testHandler->getRecords()[1];
        $this->assertSame('second message', $testMessage2['message']);
        $this->assertSame($this->channelName, $testMessage2['channel']);
        $this->assertSame('special', $testMessage2['context']['specialValue']);
        $this->assertSame('changed value', $testMessage2['context']['configuredValue']);
        $this->assertStringContainsString('myPrefix', $testMessage2['formatted']);
        $this->assertStringContainsString("{$this->channelName}.ERROR: second message", $testMessage2['formatted']);
        $this->assertStringContainsString('{"test":"testvalue"}', $testMessage2['formatted']);
    }
}
