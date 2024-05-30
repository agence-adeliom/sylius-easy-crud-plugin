<?php

declare(strict_types=1);

namespace Adeliom\SyliusEasyCrudPlugin\Helper;

/**
 * Base Enum class.
 *
 * Create an enum by implementing this class and adding class constants.
 */
/** @phpstan-consistent-constructor */
abstract class Enum implements \JsonSerializable, \Stringable
{
    /**
     * Enum value.
     */
    protected mixed $value = null;

    /**
     * Enum key, the constant name.
     */
    private string|int|null $key = null;

    /**
     * Store existing constants in a static cache per object.
     *
     * @var array<string, mixed>
     */
    protected static array $cache = [];

    /**
     * Cache of instances of the Enum class.
     *
     * @var array<string, mixed>
     */
    protected static array $instances = [];

    /**
     * Creates a new value of some type.
     *
     * @throws \UnexpectedValueException if incompatible type is given
     */
    public function __construct(mixed $value)
    {
        if ($value instanceof static) {
            $value = $value->getValue();
        }

        $this->key = static::assertValidValueReturningKey($value);

        $this->value = $value;
    }

    /**
     * This method exists only for the compatibility reason when deserializing a previously serialized version
     * that didn't had the key property.
     */
    public function __wakeup()
    {
        if (null === $this->key) {
            $search = static::search($this->value);
            if (is_string($search)) {
                $this->key = $search;
            }
        }
    }

    public static function from(mixed $value): self
    {
        $key = static::assertValidValueReturningKey($value);

        return self::__callStatic($key, []);
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Returns the enum key (i.e. the constant name).
     */
    public function getKey(): int|string|null
    {
        return $this->key;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }

    /**
     * Determines if Enum should be considered equal with the variable passed as a parameter.
     * Returns false if an argument is an object of different class or not an object.
     *
     * This method is final, for more information read https://github.com/myclabs/php-enum/issues/4
     */
    final public function equals(mixed $variable): bool
    {
        return $variable instanceof self &&
            $this->getValue() === $variable->getValue() &&
            $variable instanceof static;
    }

    /**
     * Returns the names (keys) of all constants in the Enum class.
     *
     * @return  array<mixed, int|string>
     */
    public static function keys(): array
    {
        return \array_keys(static::toArray());
    }

    /**
     * Returns instances of the Enum class of all Enum constants.
     *
     * @return array<int, mixed>
     */
    public static function values(): array
    {
        $values = [];

        foreach (static::toArray() as $key => $value) {
            $values[$key] = new static($value);
        }

        return $values;
    }

    /**
     * Returns all possible values as an array.
     *
     * @return array<int, mixed> Constant name in key, constant value in value
     */
    public static function toArray(): array
    {
        $class = static::class;

        if (!isset(static::$cache[$class])) {
            $reflection = new \ReflectionClass($class);
            static::$cache[$class] = $reflection->getConstants();
        }

        return static::$cache[$class];
    }

    /**
     * Check if is valid enum value.
     */
    public static function isValid(mixed $value): bool
    {
        return \in_array($value, static::toArray(), true);
    }

    /**
     * Asserts valid enum value.
     */
    public static function assertValidValue(mixed $value): void
    {
        self::assertValidValueReturningKey($value);
    }

    /**
     * Asserts valid enum value.
     *
     * @throws \UnexpectedValueException
     */
    protected static function assertValidValueReturningKey(mixed $value): int|string
    {
        if (false === ($key = static::search($value))) {
            throw new \UnexpectedValueException(sprintf("Value '%s' is not part of the enum ", $value) . static::class);
        }

        return $key;
    }

    /**
     * Check if is valid enum key.
     */
    public static function isValidKey(string $key): bool
    {
        $array = static::toArray();

        return isset($array[$key]) || \array_key_exists($key, $array);
    }

    /**
     * Return key for value.
     */
    public static function search(mixed $value): false|int|string
    {
        return \array_search($value, static::toArray(), true);
    }

    /**
     * Returns a value when called statically like so: MyEnum::SOME_VALUE() given SOME_VALUE is a class constant.
     *
     * @param array<mixed> $arguments
     */
    public static function __callStatic(int|string $name, array $arguments): self
    {
        $class = static::class;
        if (!isset(self::$instances[$class][$name])) {
            $array = static::toArray();
            if (!isset($array[$name]) && !\array_key_exists($name, $array)) {
                $message = sprintf("No static method or enum constant '%s' in class ", $name) . static::class;

                throw new \BadMethodCallException($message);
            }

            return self::$instances[$class][$name] = new static($array[$name]);
        }

        return clone self::$instances[$class][$name];
    }

    /**
     * Specify data which should be serialized to JSON. This method returns data that can be serialized by json_encode()
     * natively.
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): mixed
    {
        return $this->getValue();
    }
}
