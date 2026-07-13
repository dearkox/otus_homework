// Строгий режим для лучшей обработки ошибок
(function() {
    'use strict';

    // Ожидаем загрузки DOM и ядра Битрикс
    BX.ready(function() {
        // Находим обёртку грида по классу
        var wrapper = document.querySelector('.crm-notes-grid-wrapper');
        if (!wrapper) return; // Если обёртки нет — выходим

        // Получаем параметры из data-атрибутов
        var gridId = wrapper.dataset.gridId || 'crm_notes_grid';      // ID грида
        var entityType = wrapper.dataset.entityType || '';            // Тип сущности (DEAL, LEAD, CONTACT, COMPANY)
        var entityId = wrapper.dataset.entityId || 0;                // ID сущности

        // Перехватываем событие перед отправкой AJAX-запроса грида
        // Это позволяет добавить свои параметры в запрос
        BX.addCustomEvent('Grid::beforeRequest', function(grid, eventArgs) {
            // Проверяем, что событие относится к нашему гриду
            if (grid.getId() !== gridId) return;

            // Добавляем параметры в тело запроса
            // eventArgs.data — это данные, которые будут отправлены на сервер
            eventArgs.data = eventArgs.data || {};                    // Если data нет — создаём пустой объект
            eventArgs.data.PARAMS = eventArgs.data.PARAMS || {};     // Вкладываем параметры в ключ PARAMS
            eventArgs.data.PARAMS.ENTITY_TYPE = entityType;           // Тип сущности
            eventArgs.data.PARAMS.ENTITY_ID = entityId;              // ID сущности
        });
    });
})();