<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

/**
 * Описание активити "Поиск компании по ИНН"
 *
 * @var array $arActivityDescription
 */

// Подключаем языковой файл
Loc::loadMessages(__FILE__);

$arActivityDescription = [
    'NAME' => Loc::getMessage('INN_ACTIVITY_DESCR_NAME'),
    'DESCRIPTION' => Loc::getMessage('INN_ACTIVITY_DESCR_DESC'),
    'TYPE' => 'activity',
    'CLASS' => 'InnActivity',
    'JSCLASS' => 'BizProcActivity',
    'CATEGORY' => [
        'ID' => 'other',
    ],
    'FILTER' => [],  // Добавляем этот ключ
    'RETURN' => [
        'CompanyId' => [
            'NAME' => Loc::getMessage('INN_ACTIVITY_RETURN_COMPANY_ID'),
            'TYPE' => 'int',
        ],
        'CompanyName' => [
            'NAME' => Loc::getMessage('INN_ACTIVITY_RETURN_COMPANY_NAME'),
            'TYPE' => 'string',
        ],
    ],
];