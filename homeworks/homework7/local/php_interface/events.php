<?php

use Bitrix\Main\EventManager;

$eventManager = EventManager::getInstance();

$eventManager->registerEventHandler(
    'iblock',
    'OnIBlockPropertyBuildList',
    'main',
    \SergeyPr\Handlers\Iblock\Properties\ProcedureRecords::class,
    'GetUserTypeDescription'
);