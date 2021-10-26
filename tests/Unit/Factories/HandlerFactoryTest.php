<?php

namespace leinonen\Yii2Monolog\Tests\Unit\Factories;

use Mockery as m;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\GitProcessor;
use Monolog\Formatter\LineFormatter;
use leinonen\Yii2Monolog\Yii2LogMessage;
use leinonen\Yii2Monolog\Factories\HandlerFactory;
use leinonen\Yii2Monolog\Factories\GenericStrategyBasedFactory;

class HandlerFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    /** @test */
    public function itCanMakeHandlers()
    {
        $config = [
            'path' => 'app.log',
            'level' => Logger::WARNING,
            'bubble' => false,
            'filePermissions' => 'something',
            'useLocking' => true,
        ];

        $mockStreamHandler = m::mock(StreamHandler::class);

        $mockGenericFactory = m::mock(GenericStrategyBasedFactory::class);
        $mockGenericFactory->shouldReceive('makeWithStrategy')
            ->once()
            ->withArgs([StreamHandler::class, $config])
            ->andReturn($mockStreamHandler);

        $factory = new HandlerFactory($mockGenericFactory);
        $handler = $factory->make(StreamHandler::class, $config);

        $this->assertSame($mockStreamHandler, $handler);
    }

    /**
     * @test
     *
     *
     */
    public function itShouldThrowAnExceptionIfTheGivenClassNameDoesntImplementHandlerInterface()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "leinonen\Yii2Monolog\Yii2LogMessage doesn't implement Monolog\Handler\HandlerInterface"
        );
        $mockGenericFactory = m::mock(GenericStrategyBasedFactory::class);
        $factory = new HandlerFactory($mockGenericFactory);
        $factory->make(Yii2LogMessage::class);
    }

    /** @test */
    public function itCanMakeTheHandlerWithASpecificFormatter()
    {
        $lineFormatter = new LineFormatter();

        $mockStreamHandler = m::mock(StreamHandler::class);
        $mockStreamHandler->shouldReceive('setFormatter')->once()->with($lineFormatter);

        $mockGenericFactory = m::mock(GenericStrategyBasedFactory::class);
        $mockGenericFactory->shouldReceive('makeWithStrategy')
            ->once()
            ->withArgs([StreamHandler::class, []])
            ->andReturn($mockStreamHandler);

        $factory = new HandlerFactory($mockGenericFactory);
        $handler = $factory->make(StreamHandler::class, [], $lineFormatter);
        $this->assertSame($mockStreamHandler, $handler);
    }

    /** @test */
    public function itCanMakeTheHandlerWithASpecificStackOfProcessors()
    {
        $processors = [
            function ($record) {
                return $record;
            },
            new GitProcessor(),
        ];

        $mockStreamHandler = m::mock(StreamHandler::class);
        $mockStreamHandler->shouldReceive('pushProcessor')->once()->with($processors[0]);
        $mockStreamHandler->shouldReceive('pushProcessor')->once()->with($processors[1]);

        $mockGenericFactory = m::mock(GenericStrategyBasedFactory::class);
        $mockGenericFactory->shouldReceive('makeWithStrategy')
            ->once()
            ->withArgs([StreamHandler::class, []])
            ->andReturn($mockStreamHandler);

        $factory = new HandlerFactory($mockGenericFactory);

        $handler = $factory->make(StreamHandler::class, [], null, $processors);
        $this->assertSame($mockStreamHandler, $handler);
    }
}
