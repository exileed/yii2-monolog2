<?php

declare(strict_types=1);

namespace leinonen\Yii2Monolog\CreationStrategies;

use ReflectionParameter;
use InvalidArgumentException;

class ReflectionStrategy implements CreationStrategyInterface
{
    /**
     * @var \ReflectionClass
     */
    private $handlerReflectionClass;

    /**
     * Initializes a new ReflectionStrategy.
     *
     * @param string $class
     */
    public function __construct(string $class)
    {
        $this->handlerReflectionClass = new \ReflectionClass($class);
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredParameters(): array
    {
        if ($this->handlerReflectionClass->getConstructor() === null) {
            return [];
        }

        return array_map(
            static fn (ReflectionParameter $constructorParameter): string => $constructorParameter->name,
            array_filter(
                $this->getReflectionParametersFromReflectionClass(),
                static fn (ReflectionParameter $constructorParameter): bool => !$constructorParameter->isOptional(),
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getConstructorParameters(array $config): array
    {
        if ($this->handlerReflectionClass->getConstructor() === null) {
            return [];
        }

        return array_map(
            fn (ReflectionParameter $constructorParameter): mixed => $this->resolveConstructorParameterValue(
                $constructorParameter,
                $config,
            ),
            $this->getReflectionParametersFromReflectionClass(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationCallable(array $config): callable
    {
        return $config['configure'] ?? function ($instance) {
            return $instance;
        };
    }

    /**
     * Returns the value for the constructorParameter from given configuration array.
     *
     * @param ReflectionParameter $constructorParameter
     * @param array $config
     *
     * @return mixed
     *
     * @throws \yii\di\NotInstantiableException
     * @throws \yii\base\InvalidConfigException
     * @throws \InvalidArgumentException
     */
    private function resolveConstructorParameterValue(ReflectionParameter $constructorParameter, array $config)
    {
        foreach ($config as $parameterName => $configuredParameterValue) {
            if ($constructorParameter->name === $parameterName) {
                return $configuredParameterValue;
            }
        }

        if ($constructorParameter->isDefaultValueAvailable()) {
            return $constructorParameter->getDefaultValue();
        }

        if ($constructorParameter->hasType() && ! $constructorParameter->getType()->isBuiltin()) {
            return \Yii::$container->get($constructorParameter->getType()->getName());
        }

        throw new InvalidArgumentException(
            "Expected to find key: '{$constructorParameter->name}' in the given config array but none found."
        );
    }

    /**
     * Returns the constructor parameters from the reflection class the strategy was initiated with.
     *
     * @return ReflectionParameter[]
     */
    private function getReflectionParametersFromReflectionClass(): array
    {
        return $this->handlerReflectionClass->getConstructor()->getParameters();
    }
}
