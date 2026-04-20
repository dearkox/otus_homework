<?php

namespace SergeyPr\Medical;

use Bitrix\Main\Loader;
use Bitrix\Iblock\Elements\ElementProceduresTable;
use CIBlockElement;

Loader::includeModule('iblock');

/**
 * Класс для управления процедурами (добавление, получение)
 */
class ProcedureManager
{
    /**
     * Добавляет новую процедуру
     *
     * @param string $name Название процедуры
     * @return int|false ID добавленного элемента или false
     */
    public static function add(string $name)
    {
        $element = new CIBlockElement();

        $fields = [
            'IBLOCK_ID' => 17,
            'NAME' => $name,
            'ACTIVE' => 'Y',
            'PROPERTY_VALUES' => [
                'PROCEDURE' => $name,
            ]
        ];

        return $element->Add($fields);
    }

    /**
     * Возвращает список всех процедур
     *
     * @return array Массив процедур с ключами ID, NAME, PROCEDURE
     */
    public static function getAll(): array
    {
        return ElementProceduresTable::getList([
            'select' => ['ID', 'NAME', 'PROCEDURE'],
            'filter' => ['=ACTIVE' => 'Y'],
            'order' => ['NAME' => 'ASC']
        ])->fetchAll();
    }
}