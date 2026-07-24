<?php

namespace SergeyPr\Rest\Notes\Handlers;

use Bitrix\Main\Event;

/**
 * Обработчик REST-событий для сущности "Заметки"
 *
 * Подготавливает данные для отправки в REST-события:
 * - onNotesAdd — после добавления заметки
 * - onNotesUpdate — после обновления заметки
 * - onNotesDelete — после удаления заметки
 *
 * @package SergeyPr\Rest\Notes\Handlers
 */
class NotesEventHandler
{
    /**
     * Подготавливает данные для отправки в REST-событие
     *
     * @param array $arguments Аргументы события (обычно массив с объектом Event)
     * @param array $handler Описание обработчика
     * @return array
     */
    public static function prepareEventData(array $arguments, array $handler): array
    {
        // Получаем первый элемент массива — объект события
        $event = reset($arguments);

        // Если это объект Event — извлекаем его параметры
        if ($event instanceof Event) {
            return $event->getParameters();
        }

        // На случай, если пришли не Event, возвращаем как есть
        return $arguments;
    }
}