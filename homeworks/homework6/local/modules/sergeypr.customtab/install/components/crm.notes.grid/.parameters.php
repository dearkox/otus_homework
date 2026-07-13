<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

/**
 * Параметры компонента crm.notes.grid
 *
 * @var array $arCurrentValues
 */

// Подключаем языковой файл для параметров
Loc::loadMessages(__FILE__);

$arComponentParameters = [
    'PARAMETERS' => [
        // Тип CRM-сущности (DEAL, LEAD, CONTACT, COMPANY)
        'ENTITY_TYPE' => [
            'PARENT' => 'BASE',                                 // Группа параметров: основные
            'NAME' => Loc::getMessage('CRM_NOTES_GRID_PARAM_ENTITY_TYPE_NAME') ?: 'Тип сущности',
            'DESCRIPTION' => Loc::getMessage('CRM_NOTES_GRID_PARAM_ENTITY_TYPE_DESC') ?: 'Тип CRM-сущности (DEAL, LEAD, CONTACT, COMPANY)',
            'TYPE' => 'STRING',                                  // Тип: строка
            'DEFAULT' => 'DEAL',                                 // Значение по умолчанию
        ],
        // ID CRM-сущности
        'ENTITY_ID' => [
            'PARENT' => 'BASE',                                 // Группа параметров: основные
            'NAME' => Loc::getMessage('CRM_NOTES_GRID_PARAM_ENTITY_ID_NAME') ?: 'ID сущности',
            'DESCRIPTION' => Loc::getMessage('CRM_NOTES_GRID_PARAM_ENTITY_ID_DESC') ?: 'ID CRM-сущности',
            'TYPE' => 'INT',                                    // Тип: целое число
            'DEFAULT' => 0,                                     // Значение по умолчанию
        ],
    ],
];