<?php

namespace leinonen\Yii2Monolog\Tests\Unit;

use yii\log\Logger;
use Psr\Log\LogLevel;
use yii\helpers\VarDumper;
use PHPUnit\Framework\TestCase;
use leinonen\Yii2Monolog\Yii2LogMessage;

class Yii2LogMessageTest extends TestCase
{
    /** @test */
    public function itCanBeInitializedWithAYii2LogMessage()
    {
        $message = [
            'message',
            Logger::LEVEL_ERROR,
            'application',
            10,
            $this->getDummyStackTrace(),
            123,
        ];

        $logMessage = new Yii2LogMessage($message);

        $this->assertSame('message', $logMessage->getMessage());
        $this->assertSame(10, $logMessage->getTimestamp());
        $this->assertSame(
            [
                'category' => 'application',
                'trace' => $this->getDummyStackTrace(),
                'memory' => 123,
            ],
            $logMessage->getContext()
        );
    }

    /**
     * @test
     * @dataProvider psr3LogLevelDataProvider
     */
    public function itConvertsTheYii2LogLevelSuccessfullyToPsr3LogLevel($yii2LogLevel, $psr3LogLevel)
    {
        $message = [
            'message',
            $yii2LogLevel,
            'application',
            10,
            $this->getDummyStackTrace(),
            123,
        ];

        $logMessage = new Yii2LogMessage($message);

        $this->assertSame($psr3LogLevel, $logMessage->getPsr3LogLevel());
    }

    /**
     * Data provider for PSR-3 Test.
     *
     * [
     *  $yii2LogLevel,
     *  $expectedLogLevel
     * ]
     *
     * @return array
     */
    public function psr3LogLevelDataProvider()
    {
        return [
            [
                Logger::LEVEL_ERROR,
                LogLevel::ERROR,
            ],
            [
                Logger::LEVEL_WARNING,
                LogLevel::WARNING,
            ],
            [
                Logger::LEVEL_INFO,
                LogLevel::INFO,
            ],
            [
                Logger::LEVEL_TRACE,
                LogLevel::DEBUG,
            ],
            [
                Logger::LEVEL_PROFILE,
                LogLevel::DEBUG,
            ],
            [
                Logger::LEVEL_PROFILE_BEGIN,
                LogLevel::DEBUG,
            ],
            [
                Logger::LEVEL_PROFILE_END,
                LogLevel::DEBUG,
            ],
        ];
    }

    /** @test */
    public function yiisMessagesCanBeAlsoArraysInsteadOfPlainStrings()
    {
        $expectedArrayOutput = VarDumper::export(['an array as the log message']);
        $expectedMultiLevelArrayOutput = VarDumper::export([
            'an array as the log message' => ['with' => ['nested' => 'arrays']],
        ]);

        $messagesAndTheirExpectedResultsMap = [
            $expectedArrayOutput => [
                ['an array as the log message'],
                Logger::LEVEL_ERROR,
                'application',
                10,
                $this->getDummyStackTrace(),
                123,
            ],
            $expectedMultiLevelArrayOutput => [
                ['an array as the log message' => ['with' => ['nested' => 'arrays']]],
                Logger::LEVEL_ERROR,
                'application',
                10,
                $this->getDummyStackTrace(),
                123,
            ],
        ];

        foreach ($messagesAndTheirExpectedResultsMap as $expectedMessage => $yiisMessage) {
            $logMessage = new Yii2LogMessage($yiisMessage);

            $this->assertSame($expectedMessage, $logMessage->getMessage());
        }
    }

    /** @test */
    public function exceptionsAreExtractedToMonologContext()
    {
        $runTimeException = new \RuntimeException('a runtime exception');
        $message = [
            $runTimeException,
            Logger::LEVEL_ERROR,
            'application',
            10,
            $this->getDummyStackTrace(),
            123,
        ];
        $logMessage = new Yii2LogMessage($message);

        $this->assertSame('RuntimeException: a runtime exception', $logMessage->getMessage());
        $this->assertEquals($runTimeException, $logMessage->getException());
        $this->assertArrayHasKey('exception', $logMessage->getContext());
    }

    /**
     * @return array
     */
    private function getDummyStackTrace()
    {
        return [
            'file' => __FILE__,
            'line' => 62,
            'function' => 'log',
            'class' => Logger::class,
            'type' => '->',
        ];
    }
}
