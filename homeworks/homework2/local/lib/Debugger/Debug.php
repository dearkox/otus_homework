<?php

namespace SergeyPr\Debugger;

use Bitrix\Main\Diag\ExceptionHandlerFormatter;
use Bitrix\Main\Diag\FileExceptionHandlerLog;

class Debug extends FileExceptionHandlerLog
{

    private int $logLevel;

    /**
     * Сохраняем уровень логирования из настроек для использования в write()
     * Родительский initialize() при этом отрабатывает стандартно
     *
     * @param array $options
     * @return void
     */
    public function initialize(array $options)
    {
        parent::initialize($options);
        $this->logLevel = $options['level'] ?? 0;
    }

    /**
     * Переопределяет функцию для изменениея строки вывода лога
     *
     * @param $exception
     * @param $logType
     * @return void
     */
    public function write($exception, $logType): void
    {
        $text = ExceptionHandlerFormatter::format($exception, false, $this->logLevel);

        $context = [
            'type' => static::logTypeToString($logType),
        ];

        $logLevel = static::logTypeToLevel($logType);

        // Добавляем в вывод OTUS Logger
        $message = "{date} OTUS Logger - Host: {host} - {type} - {$text}\n";

        $this->logger->log($logLevel, $message, $context);
    }

    /**
     * Выводит сообщение с дампом переменной (обёртка над var_dump)
     *
     * @param mixed $data
     * @return void
     */
    public static function dump(mixed $data): void
    {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }
}