<?php

declare(strict_types=1);

namespace leinonen\Yii2Monolog;

use Monolog\Logger;
use yii\log\Target;

class MonologTarget extends Target
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $channel = null;

    /**
     * @var LoggerRegistry
     */
    private $loggerRegistry;

    /**
     * Initializes a new MonologTarget.
     *
     * @param LoggerRegistry $loggerRegistry
     * @param array $config
     */
    public function __construct(LoggerRegistry $loggerRegistry, $config = [])
    {
        $this->loggerRegistry = $loggerRegistry;
        parent::__construct($config);
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        $this->logger = $this->loggerRegistry->getLogger($this->channel);

        parent::init();
    }

    /**
     * {@inheritdoc}
     */
    public function export()
    {
        foreach ($this->getMessages() as $message) {
            $this->logger->log($message->getPsr3LogLevel(), $message->getMessage(), $message->getContext());
        }
    }

    public function setChannel(string $channel)
    {
        $this->channel = $channel;
    }

    /**
     * Returns the messages from the Target wrapped in Yii2LogMessage.
     *
     * @return Yii2LogMessage[]
     */
    private function getMessages(): array
    {
        return array_map(
            static fn (array $message): Yii2LogMessage => new Yii2LogMessage($message),
            $this->messages,
        );
    }
}
