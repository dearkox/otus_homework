<?php
/**
 * Скрипт установки REST-приложения "Дата последней коммуникации"
 *
 * Выполняется один раз при установке приложения через интерфейс Битрикс24.
 * Создаёт пользовательское поле "Дата последней коммуникации" в контактах
 * и регистрирует обработчик события onCrmActivityAdd.
 */

define('C_REST_BLOCK_LOG', true);

require_once(__DIR__ . '/crest.php');

$settings = require_once(__DIR__ . '/.settings.php');

/**
 * 1. ПРОВЕРКА СУЩЕСТВОВАНИЯ ПОЛЯ
 *
 * Используем классический crm.contact.userfield.list.
 * Фильтр ищет полное имя поля, например 'UF_CRM_LAST_COMM'.
 */
$fullFieldName = 'UF_CRM_' . $settings['userfield']['fieldName'];

$existingFields = CRest::call('crm.contact.userfield.list', [
        'filter' => [
                'FIELD_NAME' => $fullFieldName
        ]
]);

$isFieldExists = !empty($existingFields['result']);
$fieldMessage = '';

if (!$isFieldExists) {
    /**
     * 2. СОЗДАНИЕ ПОЛЯ
     *
     * Для crm.contact.userfield.add параметры ВНУТРИ fields
     * должны быть СТРОГО в ВЕРХНЕМ регистре.
     */
    $fieldResult = CRest::call('crm.contact.userfield.add', [
            'fields' => [
                    'FIELD_NAME' => $settings['userfield']['fieldName'],
                    'USER_TYPE_ID' => $settings['userfield']['userTypeId'],
                    'EDIT_FORM_LABEL' => [
                            'ru' => $settings['userfield']['label']['ru'] ?? 'Дата последней коммуникации',
                            'en' => $settings['userfield']['label']['en'] ?? 'Last communication date'
                    ],
                    'LIST_COLUMN_LABEL' => [
                            'ru' => $settings['userfield']['label']['ru'] ?? 'Дата последней коммуникации'
                    ],
                    'LIST_FILTER_LABEL' => [
                            'ru' => $settings['userfield']['label']['ru'] ?? 'Дата последней коммуникации'
                    ]
            ]
    ]);

    if (isset($fieldResult['result'])) {
        $fieldMessage = '✅ Пользовательское поле успешно создано.';
    } else {
        $fieldMessage = '❌ Ошибка создания поля: ' . ($fieldResult['error_description'] ?? 'Неизвестная ошибка API');
    }
} else {
    $fieldMessage = 'ℹ️ Поле ' . $fullFieldName . ' уже существует, создание пропущено.';
}

/**
 * 3. РЕГИСТРАЦИЯ ОБРАБОТЧИКА СОБЫТИЯ
 *
 * Используем $_SERVER['SCRIPT_NAME'] вместо REQUEST_URI,
 * чтобы полностью отсечь GET-параметры (DOMAIN, APP_SID и др.),
 * которые ломают фоновые вебхуки Битрикса.
 */
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$cleanPath = str_replace('install.php', $settings['event']['handler'], $_SERVER['SCRIPT_NAME']);
$handlerUrl = $protocol . $_SERVER['HTTP_HOST'] . $cleanPath;

// Перед привязкой нового события, сначала пробуем отвязать старое, если оно "залипло"
CRest::call('event.unbind', [
        'event' => $settings['event']['name'],
        'handler' => $handlerUrl
]);

// Теперь чисто и надежно привязываем событие заново
$eventResult = CRest::call('event.bind', [
        'event' => $settings['event']['name'],
        'handler' => $handlerUrl,
        'auth_type' => 0 // Сообщаем Битриксу, что хотим авторизацию под администратором фрейма
]);

if (isset($eventResult['result'])) {
    $eventMessage = '✅ Обработчик зарегистрирован: ' . htmlspecialchars($handlerUrl);
} else {
    $eventMessage = '❌ Ошибка регистрации: ' . ($eventResult['error_description'] ?? 'Неизвестная ошибка');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Установка приложения</title>
    <script src="//api.bitrix24.by/api/v1/"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f7fa;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #2fc6f6;
            margin-top: 0;
        }

        .success {
            color: #28a745;
        }

        .error {
            color: #dc3545;
        }

        .info {
            color: #17a2b8;
        }

        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>📅 Дата последней коммуникации</h2>
    <p>Настройка приложения завершена.</p>
    <hr>
    <p><strong>Пользовательское поле:</strong></p>
    <p><?= $fieldMessage ?></p>
    <p><strong>Обработчик события:</strong></p>
    <p><?= $eventMessage ?></p>
    <hr>
    <p style="font-size: 14px; color: #6c757d;">
        <strong>Как это работает:</strong><br>
        При создании любого дела (звонок, встреча, задача, письмо), связанного с контактом,
        в поле <b><?= $fullFieldName ?></b>
        автоматически записывается текущая дата и время.
    </p>
    <p style="font-size: 14px; color: #6c757d;">
        <strong>Код поля:</strong> <span class="code"><?= $fullFieldName ?></span>
    </p>
    <script>
        /**
         * Сообщаем Битриксу, что установка завершена.
         * Без этого вызова окно установки не закроется.
         */
        BX24.init(function () {
            BX24.installFinish();
        });
    </script>
</div>
</body>
</html>