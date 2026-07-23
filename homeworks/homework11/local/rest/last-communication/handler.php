<?php
/**
 * Обработчик события onCrmActivityAdd
 *
 * Вызывается Битрикс24 при создании дела в CRM.
 * Обновляет поле "Дата последней коммуникации" в контакте.
 */

define('C_REST_BLOCK_LOG', true);

// Защита фонового процесса от прерывания
set_time_limit(0);
ignore_user_abort(true);

/**
 * Перехватываем авторизацию до подключения CRest.
 */
if (isset($_POST['auth'])) {
    $_REQUEST['auth'] = $_POST['auth'];
}

// Подключаем SDK
require_once(__DIR__ . '/crest.php');

$settings = require_once(__DIR__ . '/.settings.php');

/**
 * Получаем ID созданного дела из POST-запроса.
 */
$activityId = (int)($_POST['data']['FIELDS']['ID'] ?? 0);

if ($activityId <= 0) {
    die('Empty activity ID');
}

/**
 * Искусственная пауза для завершения транзакции БД.
 */
sleep(2);

/**
 * Получаем информацию о деле через REST API.
 */
$activityData = CRest::call('crm.activity.get', [
    'id' => $activityId
]);

$activity = $activityData['result'] ?? null;

if (!$activity) {
    die('Activity not found in CRM');
}

/**
 * Фильтрация по направлению и провайдеру.
 *
 * ВАРИАНТ 1: Боевой (только входящие коммуникации).
 * - DIRECTION = 1 (входящее)
 * - PROVIDER_ID = VOIP, CRM_EMAIL, IMOPENLINES_PROVIDER
 */
/*
$direction = isset($activity['DIRECTION']) ? (int)$activity['DIRECTION'] : 0;
$providerId = $activity['PROVIDER_ID'] ?? '';

$allowedProviders = $settings['filters']['providers'] ?? ['VOIP', 'CRM_EMAIL', 'IMOPENLINES_PROVIDER'];
$targetDirection = $settings['filters']['direction'] ?? 1;

if ($direction !== $targetDirection || !in_array($providerId, $allowedProviders, true)) {
    die('Ignored: not a target client communication');
}
*/

/**
 * ВАРИАНТ 2: Тестовый (пропускает CRM_TODO для проверки).
 * direction = 0 — пропускает любые направления.
 * providers = VOIP, CRM_EMAIL, IMOPENLINES_PROVIDER, CRM_TODO.
 */
$direction = isset($activity['DIRECTION']) ? (int)$activity['DIRECTION'] : 0;
$providerId = $activity['PROVIDER_ID'] ?? '';

$allowedProviders = $settings['filters']['providers'] ?? ['VOIP', 'CRM_EMAIL', 'IMOPENLINES_PROVIDER', 'CRM_TODO'];
$targetDirection = $settings['filters']['direction'] ?? 0;

if ($direction !== $targetDirection || !in_array($providerId, $allowedProviders, true)) {
    die('Ignored: not a target client communication');
}

/**
 * Поиск контактов, связанных с делом.
 */
$contactIds = [];

if (isset($activity['OWNER_TYPE_ID']) && (int)$activity['OWNER_TYPE_ID'] === 3) {
    $contactIds[] = (int)$activity['OWNER_ID'];
}

if (!empty($activity['BINDINGS']) && is_array($activity['BINDINGS'])) {
    foreach ($activity['BINDINGS'] as $binding) {
        if ((int)$binding['OWNER_TYPE_ID'] === 3) {
            $contactIds[] = (int)$binding['OWNER_ID'];
        }
    }
}

$contactIds = array_unique(array_filter($contactIds));

if (empty($contactIds)) {
    die('No contacts linked to this activity');
}

/**
 * Обновляем поле у всех найденных контактов.
 */
$currentDateTime = date('c');
$fieldName = 'UF_CRM_LAST_COMM';

foreach ($contactIds as $contactId) {
    CRest::call('crm.contact.update', [
        'id' => $contactId,
        'fields' => [
            $fieldName => $currentDateTime
        ],
        'params' => [
            'REGISTER_SONET_EVENT' => 'N'
        ]
    ]);
}

echo 'OK';