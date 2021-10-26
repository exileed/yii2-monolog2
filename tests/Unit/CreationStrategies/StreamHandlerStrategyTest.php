<?php

namespace leinonen\Yii2Monolog\Tests\Unit\CreationStrategies;

use Monolog\Logger;
use ReflectionClass;
use ReflectionParameter;
use PHPUnit\Framework\TestCase;
use Monolog\Handler\StreamHandler;
use leinonen\Yii2Monolog\CreationStrategies\StreamHandlerStrategy;

class StreamHandlerStrategyTest extends TestCase
{
    /** @test */
    public function itReturnsCorrectRequiredParametersForAStreamHandler()
    {
        $constructorParameters = collect(
            (new ReflectionClass(StreamHandler::class))
                ->getConstructor()
                ->getParameters()
        );

        $requiredParameters = $constructorParameters->reject(
            function (ReflectionParameter $constructorParameter) {
                return $constructorParameter->isOptional();
            }
        )->map(
            function (ReflectionParameter $constructorParameter) {
                // We want to call the stream parameter path as Symfony does the same in it's Monolog config.
                if ($constructorParameter->name === 'stream') {
                    return 'path';
                }

                return $constructorParameter->name;
            }
        )->all();

        $strategy = new StreamHandlerStrategy();
        $this->assertSame($requiredParameters, $strategy->getRequiredParameters());
    }

    /** @test */
    public function itReturnsTheRightConstructorParameterValuesFromGivenConfig()
    {
        $config = [
            'path' => 'a stream',
            'level' => Logger::WARNING,
            'bubble' => false,
            'filePermission' => 'some',
            'useLocking' => true,
        ];

        $strategy = new StreamHandlerStrategy();
        $this->assertSame(
            [
                'a stream',
                Logger::WARNING,
                false,
                'some',
                true,
            ],
            $strategy->getConstructorParameters($config)
        );
    }

    /** @test */
    public function itShouldFallbackToCorrectDefaultsIfNoConfigGiven()
    {
        $expectedValues = [];
        // First constructor parameter is stream which is required
        $expectedValues[] = 'a stream';
        $constructorParameters = (new ReflectionClass(StreamHandler::class))->getConstructor()->getParameters();

        foreach ($constructorParameters as $constructorParameter) {
            if ($constructorParameter->isOptional()) {
                $expectedValues[] = $constructorParameter->getDefaultValue();
            }
        }

        $config = [
            'path' => 'a stream',
        ];

        $strategy = new StreamHandlerStrategy();
        $this->assertSame($expectedValues, $strategy->getConstructorParameters($config));
    }

    /** @test */
    public function itUsesYiisGetAliasToResolveThePathValue()
    {
        \Yii::setAlias('@myAlias', '/awesome');

        $config = [
            'path' => '@myAlias/test',
        ];
        $strategy = new StreamHandlerStrategy();

        $this->assertSame('/awesome/test', $strategy->getConstructorParameters($config)[0]);
    }

    /** @test */
    public function itShouldReturnACallableThatJustReturnsTheGivenInstanceFromConfigurationCallable()
    {
        $streamHandler = new StreamHandler('test');
        $strategy = new StreamHandlerStrategy();

        $configure = $strategy->getConfigurationCallable([]);

        $this->assertSame($streamHandler, $configure($streamHandler));
    }
}
