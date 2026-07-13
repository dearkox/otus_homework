<?php

namespace SergeyPr\CustomTab\ORM;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\Type\DateTime;

/**
 * ORM-класс для таблицы crm_notes
 *
 * @package SergeyPr\CustomTab\ORM
 */
class CrmNotesTable extends DataManager
{
    /**
     * Возвращает имя таблицы в БД
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'crm_notes';
    }

    /**
     * Возвращает карту полей таблицы
     *
     * @return array
     */
    public static function getMap(): array
    {
        return [
            // ID заметки (первичный ключ, автоинкремент)
            (new IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(true)
                ->configureTitle('ID'),

            // Тип CRM-сущности (DEAL, LEAD, CONTACT, COMPANY)
            (new StringField('ENTITY_TYPE'))
                ->configureRequired(true)
                ->configureSize(50)
                ->configureTitle('Тип сущности'),

            // ID CRM-сущности
            (new IntegerField('ENTITY_ID'))
                ->configureRequired(true)
                ->configureTitle('ID сущности'),

            // Текст заметки
            (new TextField('NOTE_TEXT'))
                ->configureRequired(true)
                ->configureTitle('Текст заметки'),

            // ID пользователя, создавшего заметку
            (new IntegerField('CREATED_BY'))
                ->configureRequired(true)
                ->configureTitle('Создал'),

            // Дата и время создания
            (new DatetimeField('CREATED_AT'))
                ->configureDefaultValue(new DateTime())
                ->configureTitle('Дата создания'),
        ];
    }
}