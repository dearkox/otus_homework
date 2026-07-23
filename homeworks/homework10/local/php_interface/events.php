<?php

use Bitrix\Main\EventManager;

$eventManager = EventManager::getInstance();

// Обработчик для кастомного поля
$eventManager->registerEventHandler(
    'iblock',
    'OnIBlockPropertyBuildList',
    'main',
    \SergeyPr\Handlers\Iblock\Properties\ProcedureRecords::class,
    'GetUserTypeDescription'
);

// Обработчики для инфоблока «Заявки» (ID 22)
$eventManager->registerEventHandler(
    'iblock',
    'OnAfterIBlockElementAdd',
    'main',
    \SergeyPr\Handlers\IblockRequestHandler::class,
    'onAdd'
);

$eventManager->registerEventHandler(
    'iblock',
    'OnAfterIBlockElementUpdate',
    'main',
    \SergeyPr\Handlers\IblockRequestHandler::class,
    'onUpdate'
);

// Обработчик для сделок CRM через D7-событие
$eventManager->registerEventHandler(
    'crm',
    'onAfterCrmDealUpdate',
    'main',
    \SergeyPr\Handlers\CrmDealHandler::class,
    'onDealUpdate'
);