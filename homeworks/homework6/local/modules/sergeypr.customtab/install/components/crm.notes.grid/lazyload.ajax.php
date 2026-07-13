<?php

// Отключаем сбор статистики для этого скрипта (ускоряет выполнение)
define('NO_KEEP_STATISTIC', true);

// Отключаем буферизацию статистики
define('BX_STATISTIC_BUFFER_USED', false);

// Отключаем подключение языковых файлов (ускоряет выполнение)
define('NO_LANG_FILES', true);

// Не ищем заголовок главного файла (для служебных скриптов)
define('DONT_LOOK_FOR_MAIN_FILE_HEADER', true);

// Подключаем пролог Битрикса (инициализация ядра)
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

global $USER;

// Проверка авторизации пользователя (безопасность)
if (!is_object($USER) || !$USER->IsAuthorized()) {
    die('Access denied');
}

// --- Получение параметров из запроса ---
// Параметры приходят в $_REQUEST['PARAMS']['params'] из componentData в CrmTabHandler
$params = $_REQUEST['PARAMS']['params'] ?? [];

// Извлекаем тип сущности (DEAL, LEAD, CONTACT, COMPANY)
$entityType = $params['ENTITY_TYPE'] ?? '';

// Извлекаем ID сущности и приводим к целому числу
$entityId = (int)($params['ENTITY_ID'] ?? 0);

// Очищаем тип сущности от XSS и лишних пробелов
$entityType = htmlspecialcharsbx(trim((string)$entityType));

// Приводим ID к целому числу (безопасность)
$entityId = (int)$entityId;

// Проверяем, что параметры корректны
if (empty($entityType) || $entityId <= 0) {
    // Если админ — показываем подробную информацию для отладки
    if ($USER->IsAdmin()) {
        echo "Ошибка! Данные не найдены. <pre>" . print_r($_REQUEST, true) . "</pre>";
    } else {
        // Для обычных пользователей — общее сообщение об ошибке
        echo "Неверные параметры сущности.";
    }
    require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
    die();
}

// Выводим только AJAX-контент (без шапки и футера)
$APPLICATION->ShowAjaxHead();

// Вызываем наш компонент
$APPLICATION->IncludeComponent(
    'sergeypr:crm.notes.grid',
    '',                             // Шаблон по умолчанию
    [
        'ENTITY_TYPE' => $entityType, // Тип сущности (DEAL, LEAD, CONTACT, COMPANY)
        'ENTITY_ID' => $entityId,     // ID сущности
    ],
    false                           // Не возвращать результат, а выводить сразу
);

// Подключаем эпилог (завершение работы)
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');