<?php

// Защита от прямого доступа
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

// Импортируем класс локализации
use Bitrix\Main\Localization\Loc;

/**
 * @var array $arResult       - Результат работы компонента (данные для вывода)
 * @var array $arParams        - Параметры компонента
 * @var CBitrixComponent $component - Объект компонента
 */

// Подключаем JS-скрипт для работы с гридом
// $component->getPath() возвращает путь к компоненту
$APPLICATION->AddHeadScript($component->getPath() . '/templates/.default/script.js');

// Если есть ошибка — выводим её в виде предупреждения
if (!empty($arResult['ERROR'])): ?>
    <div class="ui-alert ui-alert-danger">
        <span class="ui-alert-message"><?= htmlspecialchars($arResult['ERROR']) ?></span>
    </div>
    <?php return; // Прерываем выполнение шаблона ?>
<?php endif; ?>

<!-- Обёртка для грида с data-атрибутами для JS -->
<div class="crm-notes-grid-wrapper"
     data-grid-id="<?= htmlspecialchars($arResult['GRID_ID']) ?>"
     data-entity-type="<?= htmlspecialchars($arParams['ENTITY_TYPE']) ?>"
     data-entity-id="<?= (int)$arParams['ENTITY_ID'] ?>">
<?php
// --- Подключение стандартного компонента main.ui.grid ---
$APPLICATION->IncludeComponent(
        'bitrix:main.ui.grid',                       // Имя компонента
        '',                                          // Шаблон (пустой — .default)
        [
            // Уникальный идентификатор грида (для сохранения настроек)
                'GRID_ID' => $arResult['GRID_ID'],

            // Массив колонок
            // Каждая колонка: id — идентификатор, name — название, default — показывать по умолчанию
                'COLUMNS' => $arResult['COLUMNS'],

            // Массив строк данных
            // Каждая строка: data — ассоциативный массив с данными
                'ROWS' => $arResult['ROWS'],

            // Отключаем чекбоксы для выбора строк (не нужны в этой задаче)
                'SHOW_ROW_CHECKBOXES' => false,

            // Объект постраничной навигации
                'NAV_OBJECT' => $arResult['NAV_OBJECT'],

            // Включаем AJAX-режим (сортировка и пагинация без перезагрузки)
                'AJAX_MODE' => 'Y',

            // Не прокручивать страницу к гриду после AJAX-обновления
                'AJAX_OPTION_JUMP' => 'N',

            // Не перезагружать стили при AJAX-запросе
                'AJAX_OPTION_STYLE' => 'N',

            // Не сохранять состояние в истории браузера
                'AJAX_OPTION_HISTORY' => 'N',

            // Общее количество записей (для пагинации)
                'TOTAL_ROWS_COUNT' => $arResult['NAV_OBJECT']->getRecordCount(),

            // Доступные варианты количества записей на странице
                'PAGE_SIZES' => [
                        ['NAME' => '5', 'VALUE' => '5'],     // 5 записей
                        ['NAME' => '10', 'VALUE' => '10'],   // 10 записей
                        ['NAME' => '20', 'VALUE' => '20'],   // 20 записей
                        ['NAME' => '50', 'VALUE' => '50'],   // 50 записей
                ],

            // Количество записей на странице по умолчанию
                'DEFAULT_PAGE_SIZE' => 10,
        ]
);
?>
</div>