<?php

// Защита от прямого доступа
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

// Импортируем необходимые классы
use Bitrix\Main\Grid\Options as GridOptions;          // Работа с настройками грида (сортировка, пагинация)
use Bitrix\Main\Loader;                                 // Загрузка модулей
use Bitrix\Main\Localization\Loc;                       // Локализация (языковые фразы)
use Bitrix\Main\UI\PageNavigation;                      // Постраничная навигация
use SergeyPr\CustomTab\ORM\CrmNotesTable;               // ORM-класс для таблицы crm_notes

/**
 * Компонент для вывода заметок CRM в виде грида
 *
 * Параметры:
 * - ENTITY_TYPE — тип CRM-сущности (DEAL, LEAD, CONTACT, COMPANY)
 * - ENTITY_ID — ID CRM-сущности
 *
 * @package SergeyPr\CustomTab\Components
 */
class CrmNotesGridComponent extends CBitrixComponent
{
    /**
     * Идентификатор грида по умолчанию
     *
     * @var string
     */
    protected const GRID_ID = 'crm_notes_grid';

    /**
     * Обработка входных параметров компонента
     *
     * Приводит параметры к нужному типу и устанавливает значения по умолчанию
     *
     * @param array $arParams Входные параметры
     * @return array Обработанные параметры
     */
    public function onPrepareComponentParams($arParams): array
    {
        // Очищаем ENTITY_TYPE от пробелов
        $arParams['ENTITY_TYPE'] = trim($arParams['ENTITY_TYPE'] ?? '');
        // Приводим ENTITY_ID к целому числу
        $arParams['ENTITY_ID'] = (int)($arParams['ENTITY_ID'] ?? 0);
        // Если GRID_ID не передан — используем значение по умолчанию
        $arParams['GRID_ID'] = $arParams['GRID_ID'] ?? self::GRID_ID;

        return $arParams;
    }

    /**
     * Точка входа в компонент
     *
     * Основная логика: проверка модулей, параметров, получение данных, вывод шаблона
     *
     * @return void
     */
    public function executeComponent(): void
    {
        // Проверяем, что необходимые модули загружены
        if (!$this->checkModules()) {
            // Если модули не загружены — выводим шаблон с ошибкой
            $this->includeComponentTemplate();
            return;
        }

        // Проверяем, что обязательные параметры переданы
        if (empty($this->arParams['ENTITY_TYPE']) || $this->arParams['ENTITY_ID'] <= 0) {
            // Если параметры не переданы — выводим ошибку
            $this->arResult['ERROR'] = Loc::getMessage('CRM_NOTES_GRID_ERROR_REQUIRED_PARAMS') ?: 'Не переданы обязательные параметры';
            $this->includeComponentTemplate();
            return;
        }

        // Получаем данные для грида
        $this->arResult['GRID_ID'] = $this->arParams['GRID_ID'];                    // Уникальный ID грида
        $this->arResult['COLUMNS'] = $this->getGridColumns();                       // Настройки колонок
        $this->arResult['ROWS'] = $this->getGridData();                            // Данные для строк
        $this->arResult['NAV_OBJECT'] = $this->getNavigation();                    // Объект пагинации

        // Подключаем шаблон компонента
        $this->includeComponentTemplate();
    }

    /**
     * Проверка загрузки необходимых модулей
     *
     * @return bool
     */
    protected function checkModules(): bool
    {
        // Проверяем модуль CRM
        if (!Loader::includeModule('crm')) {
            $this->arResult['ERROR'] = Loc::getMessage('CRM_NOTES_GRID_ERROR_MODULE_CRM') ?: 'Модуль CRM не установлен';
            return false;
        }

        // Проверяем наш кастомный модуль
        if (!Loader::includeModule('sergeypr.customtab')) {
            $this->arResult['ERROR'] = Loc::getMessage('CRM_NOTES_GRID_ERROR_MODULE_CUSTOMTAB') ?: 'Модуль sergeypr.customtab не установлен';
            return false;
        }

        return true;
    }

    /**
     * Возвращает настройки колонок грида
     *
     * Каждая колонка содержит:
     * - id — уникальный идентификатор колонки
     * - name — отображаемое название
     * - default — показывать ли колонку по умолчанию
     *
     * @return array
     */
    protected function getGridColumns(): array
    {
        return [
            [
                'id' => 'ID',                                                       // Идентификатор колонки
                'name' => Loc::getMessage('CRM_NOTES_GRID_COLUMN_ID') ?: 'ID',     // Название колонки
                'default' => true,                                                 // Показывать по умолчанию
            ],
            [
                'id' => 'NOTE_TEXT',                                               // Идентификатор колонки
                'name' => Loc::getMessage('CRM_NOTES_GRID_COLUMN_NOTE') ?: 'Заметка', // Название колонки
                'default' => true,                                                 // Показывать по умолчанию
            ],
            [
                'id' => 'CREATED_BY',                                              // Идентификатор колонки
                'name' => Loc::getMessage('CRM_NOTES_GRID_COLUMN_CREATED_BY') ?: 'Кем создана', // Название колонки
                'default' => true,                                                 // Показывать по умолчанию
            ],
            [
                'id' => 'CREATED_AT',                                              // Идентификатор колонки
                'name' => Loc::getMessage('CRM_NOTES_GRID_COLUMN_CREATED_AT') ?: 'Дата создания', // Название колонки
                'default' => true,                                                 // Показывать по умолчанию
            ],
        ];
    }

    /**
     * Получение данных для грида
     *
     * Использует GridOptions для получения настроек сортировки и пагинации,
     * затем выполняет запрос к таблице crm_notes
     *
     * @return array
     */
    protected function getGridData(): array
    {
        $rows = [];

        // Получаем настройки грида (сортировка, пагинация)
        $gridOptions = new GridOptions($this->arParams['GRID_ID']);               // Объект настроек грида
        $sort = $gridOptions->getSorting(['sort' => ['ID' => 'ASC']])['sort'];    // Текущая сортировка
        $navParams = $gridOptions->getNavParams();                               // Параметры пагинации

        // Фильтр для выборки записей
        $filter = [
            '=ENTITY_TYPE' => $this->arParams['ENTITY_TYPE'],                    // Тип сущности (DEAL, LEAD, CONTACT, COMPANY)
            '=ENTITY_ID' => $this->arParams['ENTITY_ID'],                        // ID сущности
        ];

        // Считаем общее количество записей для пагинации
        $totalCount = CrmNotesTable::getList([
            'filter' => $filter,
            'count_total' => true,
        ])->getCount();

        // Настраиваем пагинацию
        $navigation = $this->getNavigation();                                  // Получаем объект пагинации
        $navigation->setRecordCount($totalCount);                              // Устанавливаем общее количество записей
        $navigation->setPageSize($navParams['nPageSize']);                     // Устанавливаем количество записей на странице

        // Выполняем выборку данных
        $items = CrmNotesTable::getList([
            'select' => ['ID', 'NOTE_TEXT', 'CREATED_BY', 'CREATED_AT'],       // Поля для выборки
            'filter' => $filter,                                               // Условия фильтрации
            'order' => $sort,                                                  // Сортировка
            'limit' => $navParams['nPageSize'],                                // Лимит записей
            'offset' => $navigation->getOffset(),                              // Смещение для пагинации
        ]);

        // Формируем строки для грида
        foreach ($items as $item) {
            $rows[] = [
                'data' => [
                    'ID' => $item['ID'],                                            // ID заметки
                    'NOTE_TEXT' => htmlspecialchars($item['NOTE_TEXT']),            // Текст заметки (безопасный вывод)
                    'CREATED_BY' => $this->getUserName($item['CREATED_BY']),        // Имя создателя
                    'CREATED_AT' => $item['CREATED_AT'] instanceof \Bitrix\Main\Type\DateTime
                        ? $item['CREATED_AT']->format('d.m.Y H:i:s')         // Дата создания в формате дд.мм.гггг чч:мм:сс
                        : $item['CREATED_AT'],
                ],
            ];
        }

        return $rows;
    }

    /**
     * Возвращает объект постраничной навигации
     *
     * @return PageNavigation
     */
    protected function getNavigation(): PageNavigation
    {
        $nav = new PageNavigation('crm-notes-grid-page');                      // Уникальный ID для навигации
        $nav->initFromUri();                                                  // Инициализация из URL (параметры page, size)
        return $nav;
    }

    /**
     * Получение имени пользователя по ID
     *
     * @param int $userId
     * @return string
     */
    protected function getUserName(int $userId): string
    {
        // Получаем данные пользователя
        $userData = \CUser::GetByID($userId)->Fetch();

        // Если пользователь найден — возвращаем Имя + Фамилию
        if ($userData) {
            return trim($userData['NAME'] . ' ' . $userData['LAST_NAME']);
        }

        // Если пользователь не найден — возвращаем "Неизвестный пользователь"
        return Loc::getMessage('CRM_NOTES_GRID_USER_UNKNOWN') ?: 'Неизвестный пользователь';
    }
}