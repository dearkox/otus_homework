<?php

namespace SergeyPr\Handlers;

use Bitrix\Main\Loader;

/**
 * Обработчик событий сделок CRM
 *
 * Синхронизирует данные между сделкой и привязанной заявкой.
 * При изменении суммы или ответственного в сделке — обновляет соответствующие поля в заявке.
 *
 * @package SergeyPr\Handlers
 */
class CrmDealHandler
{
    /**
     * Флаг для предотвращения рекурсии
     *
     * Защищает от бесконечного цикла, когда обновление сделки
     * триггерит обновление заявки, которое снова триггерит обновление сделки.
     * Используется совместно с IblockRequestHandler::$disableSync.
     *
     * @var bool
     */
    public static bool $disableSync = false;

    /**
     * ID инфоблока «Заявки»
     */
    private const IBLOCK_ID = 22;

    /**
     * Код свойства «Сделка» — привязка к элементам CRM
     */
    private const PROPERTY_DEAL = 'DEAL';

    /**
     * Код свойства «Сумма» — числовое поле
     */
    private const PROPERTY_SUM = 'SUM';

    /**
     * Код свойства «Ответственный» — привязка к сотруднику
     */
    private const PROPERTY_ASSIGNED = 'ASSIGNED';

    /**
     * Обработчик события OnCrmDealUpdate / onAfterCrmDealUpdate
     *
     * Универсальный метод, поддерживает как старый массив полей (OnCrmDealUpdate),
     * так и современный Event-объект D7 (onAfterCrmDealUpdate).
     * Приводит все ключи к верхнему регистру для универсальной работы.
     *
     * @param mixed $event Массив полей сделки или Event-объект D7
     * @return void
     */
    public static function onDealUpdate(mixed $event): void
    {
        // Защита от рекурсии
        if (self::$disableSync) {
            return;
        }

        $dealId = 0;
        $arFields = [];

        if (is_array($event)) {
            $dealId = (int)($event['ID'] ?? 0);
            $arFields = $event;
        } elseif ($event instanceof \Bitrix\Main\Event) {
            $dealId = (int)($event->getParameter('ID') ?? 0);
            $arFields = $event->getParameter('FIELDS') ?? [];
        }

        if ($dealId <= 0 || empty($arFields)) {
            return;
        }

        // Приводим все ключи к верхнему регистру для универсальности
        // D7-событие возвращает ключи в CamelCase (opportunity, assignedById)
        // Старое событие — в UPPER_CASE (OPPORTUNITY, ASSIGNED_BY_ID)
        $fieldsUpper = array_change_key_case($arFields, CASE_UPPER);

        // Собираем только те поля, которые действительно изменились
        $propertyValues = [];

        if (array_key_exists('OPPORTUNITY', $fieldsUpper)) {
            $propertyValues[self::PROPERTY_SUM] = (float)$fieldsUpper['OPPORTUNITY'];
        }

        if (array_key_exists('ASSIGNED_BY_ID', $fieldsUpper)) {
            $propertyValues[self::PROPERTY_ASSIGNED] = (int)$fieldsUpper['ASSIGNED_BY_ID'];
        }

        // Если целевые поля не менялись — выходим
        if (empty($propertyValues)) {
            return;
        }

        Loader::includeModule('iblock');

        // Ищем заявку, привязанную к этой сделке
        $request = self::findRequestByDeal($dealId);
        if (!$request) {
            return;
        }

        // Блокируем синхронизацию инфоблока
        if (class_exists('\SergeyPr\Handlers\IblockRequestHandler')) {
            \SergeyPr\Handlers\IblockRequestHandler::$disableSync = true;
        }

        // Обновляем только изменившиеся свойства заявки
        \CIBlockElement::SetPropertyValuesEx(
            (int)$request['ID'],
            self::IBLOCK_ID,
            $propertyValues
        );

        // Разблокируем синхронизацию
        if (class_exists('\SergeyPr\Handlers\IblockRequestHandler')) {
            \SergeyPr\Handlers\IblockRequestHandler::$disableSync = false;
        }
    }

    /**
     * Поиск заявки по ID сделки
     *
     * Использует классический \CIBlockElement::GetList для поиска,
     * так как свойства инфоблока не доступны через ORM напрямую.
     *
     * @param int $dealId ID сделки
     * @return array|null Массив с ID заявки или null, если заявка не найдена
     */
    protected static function findRequestByDeal(int $dealId): ?array
    {
        $dbResult = \CIBlockElement::GetList(
            [], // Сортировка не нужна
            [
                'IBLOCK_ID' => self::IBLOCK_ID,
                '=PROPERTY_' . self::PROPERTY_DEAL => $dealId,
            ],
            false, // Группировка не нужна
            ['nTopCount' => 1], // Берём только одну запись
            ['ID'] // Выбираем только ID
        );

        $res = $dbResult->Fetch();
        return $res ?: null;
    }
}