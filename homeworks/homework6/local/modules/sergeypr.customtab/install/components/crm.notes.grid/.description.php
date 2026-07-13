<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

/**
 * Описание компонента crm.notes.grid
 *
 * @var array $arComponentDescription
 */

// Подключаем языковой файл для описания компонента
Loc::loadMessages(__FILE__);

$arComponentDescription = [
    'NAME' => Loc::getMessage('CRM_NOTES_GRID_COMPONENT_NAME') ?: 'Список заметок CRM',
    'DESCRIPTION' => Loc::getMessage('CRM_NOTES_GRID_COMPONENT_DESC') ?: 'Выводит список заметок для CRM-сущности (сделки, лиды, контакты, компании)',
    'ICON' => '',
    'SORT' => 10,
    'CACHE_PATH' => 'Y',
    'PATH' => [
        'ID' => 'sergeypr',
        'NAME' => Loc::getMessage('CRM_NOTES_GRID_COMPONENT_GROUP') ?: 'SergeyPr',
    ],
];