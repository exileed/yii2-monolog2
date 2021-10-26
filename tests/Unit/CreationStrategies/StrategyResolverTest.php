<?php

namespace leinonen\Yii2Monolog\Tests\Unit\CreationStrategies;

use PHPUnit\Framework\TestCase;
use Monolog\Handler\StreamHandler;
use leinonen\Yii2Monolog\Yii2LogMessage;
use leinonen\Yii2Monolog\CreationStrategies\StrategyResolver;
use leinonen\Yii2Monolog\CreationStrategies\ReflectionStrategy;
use leinonen\Yii2Monolog\CreationStrategies\StreamHandlerStrategy;

class StrategyResolverTest extends TestCase
{
    /** @test */
    public function itCanResolveACreationStrategyForAGivenHandler()
    {
        $resolver = new StrategyResolver();

        $this->assertInstanceOf(StreamHandlerStrategy::class, $resolver->resolve(StreamHandler::class));
    }

    /** @test */
    public function itResolvesToReflectionHandlerCreationStrategyForHandlerClassesThatDoNotHaveCorrespondingCreationStrategy()
    {
        $resolver = new StrategyResolver();

        $this->assertInstanceOf(ReflectionStrategy::class, $resolver->resolve(Yii2LogMessage::class));
    }
}
