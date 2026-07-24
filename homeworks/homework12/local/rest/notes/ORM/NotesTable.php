<?php

namespace SergeyPr\Rest\Notes\ORM;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\Type\DateTime;

/**
 * ORM-класс для работы с таблицей otus_notes
 *
 * @package SergeyPr\Rest\Notes\ORM
 */
class NotesTable extends DataManager
{
    /**
     * Возвращает имя таблицы в БД
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'otus_notes';
    }

    /**
     * Возвращает карту полей таблицы
     *
     * @return array
     */
    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(true)
                ->configureTitle('ID'),

            (new StringField('TITLE'))
                ->configureRequired(true)
                ->configureSize(255)
                ->configureTitle('Название'),

            (new TextField('TEXT'))
                ->configureRequired(false)
                ->configureTitle('Текст'),

            (new IntegerField('CREATED_BY'))
                ->configureRequired(true)
                ->configureTitle('Создал'),

            (new DatetimeField('CREATED_AT'))
                ->configureDefaultValue(new DateTime())
                ->configureTitle('Дата создания'),

            (new DatetimeField('UPDATED_AT'))
                ->configureDefaultValue(new DateTime())
                ->configureTitle('Дата обновления'),
        ];
    }
}