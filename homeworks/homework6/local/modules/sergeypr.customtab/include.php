<?php

use Bitrix\Main\Loader;

/**
 * Регистрация автозагрузки классов модуля sergeypr.customtab
 *
 * Классы лежат в пространстве имён SergeyPr\CustomTab
 * Физический путь: /local/modules/sergeypr.customtab/lib/
 */
Loader::registerAutoLoadClasses(
    'sergeypr.customtab',
    [
        'SergeyPr\\CustomTab\\ORM\\CrmNotesTable' => 'lib/ORM/CrmNotesTable.php',
        'SergeyPr\\CustomTab\\Handlers\\CrmTabHandler' => 'lib/Handlers/CrmTabHandler.php',
        // при необходимости добавятся другие классы
    ]
);