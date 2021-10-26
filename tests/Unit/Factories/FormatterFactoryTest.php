<?php

namespace leinonen\Yii2Monolog\Tests\Unit\Factories;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Monolog\Formatter\LineFormatter;
use leinonen\Yii2Monolog\Yii2LogMessage;
use leinonen\Yii2Monolog\Factories\FormatterFactory;
use leinonen\Yii2Monolog\Factories\GenericStrategyBasedFactory;

class FormatterFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    /** @test */
    public function itCanMakeAFormatter()
    {
        $config = [
            'key' => 'value',
        ];

        $mockLineFormatter = m::mock(LineFormatter::class);

        $mockGenericFactory = m::mock(GenericStrategyBasedFactory::class);
        $mockGenericFactory->shouldReceive('makeWithStrategy')->once()
            ->withArgs([LineFormatter::class, $config])
            ->andReturn($mockLineFormatter);

        $factory = new FormatterFactory($mockGenericFactory);

        $formatter = $factory->make(LineFormatter::class, $config);
        $this->assertSame($mockLineFormatter, $formatter);
    }

    /**
     * @test
     *
     *
     */
    public function itShouldThrowAnExceptionIfTheGivenClassNameDoesntImplementFormatterInterface()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "leinonen\Yii2Monolog\Yii2LogMessage doesn't implement Monolog\Formatter\FormatterInterface"
        );
        $mockGenericFactory = m::mock(GenericStrategyBasedFactory::class);
        $factory = new FormatterFactory($mockGenericFactory);
        $factory->make(Yii2LogMessage::class);
    }
}
