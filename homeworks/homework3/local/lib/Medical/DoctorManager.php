<?php

namespace SergeyPr\Medical;

use Bitrix\Main\Loader;
use CIBlockElement;

Loader::includeModule('iblock');

/**
 * Класс для управления врачами (добавление, редактирование)
 */
class DoctorManager
{
    /**
     * Добавляет нового врача
     *
     * @param string $code Символьный код (латиницей)
     * @param string $lastName Фамилия
     * @param string $firstName Имя
     * @param string $middleName Отчество
     * @param array $procedureIds Массив ID процедур
     * @return int|false ID добавленного элемента или false
     */
    public static function add(string $code, string $lastName, string $firstName, string $middleName, array $procedureIds)
    {
        $element = new CIBlockElement();

        $fullName = trim($lastName . ' ' . $firstName . ' ' . $middleName);

        $fields = [
            'IBLOCK_ID' => 16,
            'NAME' => $fullName,
            'CODE' => $code,
            'ACTIVE' => 'Y',
            'PROPERTY_VALUES' => [
                'LAST_NAME' => $lastName,
                'FIRST_NAME' => $firstName,
                'MIDDLE_NAME' => $middleName,
                'PROCEDURES' => $procedureIds,
            ]
        ];

        return $element->Add($fields);
    }

    /**
     * Обновляет данные врача
     *
     * @param int $elementId ID элемента
     * @param string $code Символьный код
     * @param string $lastName Фамилия
     * @param string $firstName Имя
     * @param string $middleName Отчество
     * @param array $procedureIds Массив ID процедур
     * @return bool
     */
    public static function update(int $elementId, string $code, string $lastName, string $firstName, string $middleName, array $procedureIds): bool
    {
        $element = new CIBlockElement();

        $fullName = trim($lastName . ' ' . $firstName . ' ' . $middleName);

        $fields = [
            'NAME' => $fullName,
            'CODE' => $code,
            'PROPERTY_VALUES' => [
                'LAST_NAME' => $lastName,
                'FIRST_NAME' => $firstName,
                'MIDDLE_NAME' => $middleName,
                'PROCEDURES' => $procedureIds,
            ]
        ];

        return $element->Update($elementId, $fields);
    }
}