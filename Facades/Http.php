<?php

namespace Aurora\System\Facades;

use MailSo\Base\Http as HttpBase;
use BadMethodCallException;

class Http
{
    protected static function getInstance(): HttpBase
    {
        return HttpBase::SingletonInstance();
    }

    public static function __callStatic(string $method, array $arguments)
    {
        $instance = static::getInstance();

        if (!method_exists($instance, $method)) {
            throw new BadMethodCallException(
                sprintf('Method %s::%s does not exist.', get_class($instance), $method)
            );
        }

        return $instance->$method(...$arguments);
    }
}