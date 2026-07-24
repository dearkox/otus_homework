<?php

namespace SergeyPr\Rest\Notes\Handlers;

use Bitrix\Main\Localization\Loc;
use CRestUtil;

/**
 * Обработчик события OnRestServiceBuildDescription
 *
 * Регистрирует свои REST-методы и REST-события для сущности "Заметки"
 *
 * @package SergeyPr\Rest\Notes\Handlers
 */
class NotesRestHandler
{
    /**
     * Возвращает описание REST-методов и REST-событий для сущности "Заметки"
     *
     * @return array
     */
    public static function onRestServiceBuildDescription(): array
    {
        // Подключаем языковой файл для локализации scope
        Loc::loadMessages(__DIR__ . '/../lang/ru/index.php');

        return [
            'otus.notes' => [
                // CRUD-методы
                'otus.notes.add'    => [NotesCrudHandler::class, 'add'],
                'otus.notes.update' => [NotesCrudHandler::class, 'update'],
                'otus.notes.delete' => [NotesCrudHandler::class, 'delete'],
                'otus.notes.get'    => [NotesCrudHandler::class, 'get'],
                'otus.notes.list'   => [NotesCrudHandler::class, 'list'],

                // REST-события
                CRestUtil::EVENTS => [
                    'onNotesAdd' => [
                        'main',
                        'onAfterNotesAdd',
                        [NotesEventHandler::class, 'prepareEventData']
                    ],
                    'onNotesUpdate' => [
                        'main',
                        'onAfterNotesUpdate',
                        [NotesEventHandler::class, 'prepareEventData']
                    ],
                    'onNotesDelete' => [
                        'main',
                        'onAfterNotesDelete',
                        [NotesEventHandler::class, 'prepareEventData']
                    ],
                ],
            ],
        ];
    }
}