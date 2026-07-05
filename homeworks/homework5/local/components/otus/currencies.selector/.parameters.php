<?php

use Bitrix\Main\Loader;
use Bitrix\Currency\CurrencyLangTable;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

$arCurrencies = [];

if (Loader::includeModule('currency')) {
    $list = CurrencyLangTable::getList([
        'select' => ['CURRENCY', 'FULL_NAME'],
        'filter' => ['=LID' => LANGUAGE_ID],
        'order' => ['CURRENCY' => 'ASC'],
    ]);
    while ($item = $list->fetch()) {
        $arCurrencies[$item['CURRENCY']] = $item['CURRENCY'] . ' – ' . $item['FULL_NAME'];
    }
}

$arComponentParameters = [
    'PARAMETERS' => [
        'CURRENCY' => [
            'PARENT' => 'BASE',          // Группа параметров (BASE — основные параметры)
            'NAME' => 'Валюта',           // Название параметра, отображаемое в настройках компонента
            'TYPE' => 'LIST',             // Тип элемента управления: выпадающий список
            'VALUES' => $arCurrencies,    // Массив значений для списка в формате [код => название]
            'MULTIPLE' => 'N',            // Множественный выбор: N — одиночный, Y — множественный
            'DEFAULT' => 'USD',           // Значение по умолчанию (если в настройках ничего не выбрано)
        ],
    ],
];