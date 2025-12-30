<?php

namespace astuteo\pjeShared\helpers;

use Craft;
use craft\base\Component;
use craft\helpers\DateTimeHelper;
use Psr\Log\LogLevel;

class HelpersService extends Component
{
    private const LOG_CATEGORY = 'pje-shared';

    public static function clean($string): string
    {
        return trim($string);
    }

    public static function now(): string
    {
        $now = DateTimeHelper::currentTimeStamp();
        return DateTimeHelper::toIso8601($now);
    }

    private static function writeLog(string $level, mixed $message): void
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $trace[2] ?? $trace[1] ?? [];
        $file = $caller['file'] ?? 'unknown';
        $line = $caller['line'] ?? 0;

        $formattedMessage = sprintf(
            '[pje-shared] [%s:%d] %s',
            basename($file),
            $line,
            is_string($message) ? $message : print_r($message, true)
        );

        Craft::getLogger()->log($formattedMessage, $level, self::LOG_CATEGORY);
    }

    /**
     * @deprecated Use info(), warning(), error(), or debug() instead
     */
    public static function log(mixed $message): void
    {
        self::writeLog(LogLevel::INFO, $message);
    }

    public static function info(mixed $message): void
    {
        if (Craft::$app->config->general->devMode) {
            self::writeLog(LogLevel::INFO, $message);
        }
    }

    public static function error(mixed $message): void
    {
        self::writeLog(LogLevel::ERROR, $message);
    }

    public static function warning(mixed $message): void
    {
        self::writeLog(LogLevel::WARNING, $message);
    }

    public static function debug(mixed $message): void
    {
        if (Craft::$app->config->general->devMode) {
            self::writeLog(LogLevel::DEBUG, $message);
        }
    }
}
