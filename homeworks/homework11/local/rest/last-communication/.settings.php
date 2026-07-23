<?php
/**
 * Настройки приложения "Дата последней коммуникации"
 */

return [
    /**
     * Настройки пользовательского поля "Дата последней коммуникации"
     */
    'userfield' => [
        'entityId' => 'CRM_CONTACT',
        'fieldName' => 'LAST_COMM',
        'userTypeId' => 'datetime',
        'label' => [
            'ru' => 'Дата последней коммуникации',
            'en' => 'Last communication date'
        ],
        'showInList' => 'Y',
        'editInList' => 'Y',
    ],

    /**
     * Настройки события и обработчика
     */
    'event' => [
        'name' => 'onCrmActivityAdd',
        'handler' => 'handler.php',
    ],

    /**
     * Фильтрация типов коммуникаций
     *
     * direction: 1 — входящие, 2 — исходящие, 0 — любые
     * providers: массив провайдеров
     *
     */

    'filters' => [
        'direction' => 0,
        'providers' => ['CRM_TODO'],
    ],



];