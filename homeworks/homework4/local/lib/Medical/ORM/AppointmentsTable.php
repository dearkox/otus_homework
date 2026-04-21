<?php

namespace SergeyPr\Medical\ORM;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\ExpressionField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use Bitrix\Crm\ContactTable;

/**
 * Class AppointmentsTable
 *
 * Модель для работы с таблицей appointments (записи на приём)
 *
 * @package SergeyPr\Medical\ORM
 */
class AppointmentsTable extends DataManager
{
    /**
     * Возвращает имя таблицы в базе данных
     *
     * @return string
     */
    public static function getTableName(): string
    {
        return 'appointments';
    }

    /**
     * Возвращает карту полей сущности
     *
     * @return array
     */
    public static function getMap()
    {
        return [
            // Реальные поля таблицы
            (new IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
                'title' => 'ID записи',
            ])),

            (new IntegerField('CRM_CONTACT_ID', [
                'required' => true,
                'title' => 'ID контакта CRM',
            ])),

            (new IntegerField('DOCTOR_ID', [
                'required' => true,
                'title' => 'ID врача',
            ])),

            (new IntegerField('PROCEDURE_ID', [
                'required' => true,
                'title' => 'ID процедуры',
            ])),

            (new DatetimeField('APPOINTMENT_DATE', [
                'required' => true,
                'title' => 'Дата и время приёма',
                'validation' => [__CLASS__, 'validateAppointmentDate'],
            ])),

            (new DatetimeField('CREATED_AT', [
                'default' => function() {
                    return new DateTime();
                },
                'title' => 'Дата создания',
            ])),

            // Связь с CRM-контактом
            (new Reference(
                'CONTACT',
                ContactTable::class,
                Join::on('this.CRM_CONTACT_ID', 'ref.ID')
            ))->configureJoinType('inner'),

            // Виртуальные поля из контакта
            (new ExpressionField(
                'FIRST_NAME',
                '%s',
                ['CONTACT.NAME']
            ))->configureTitle('Имя'),

            (new ExpressionField(
                'LAST_NAME',
                '%s',
                ['CONTACT.LAST_NAME']
            ))->configureTitle('Фамилия'),

            (new ExpressionField(
                'PHONE',
                '%s',
                ['CONTACT.PHONE']
            ))->configureTitle('Телефон'),
        ];
    }

    /**
     * Валидатор для поля APPOINTMENT_DATE
     *
     * @return array
     */
    public static function validateAppointmentDate(): array
    {
        return [
            new \Bitrix\Main\ORM\Fields\Validators\RegExpValidator(
                '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
                'Формат даты должен быть ГГГГ-ММ-ДД ЧЧ:ММ:СС'
            ),
        ];
    }
}