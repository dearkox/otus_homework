<?php

namespace SergeyPr\Handlers;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Service\Container;

/**
 * Обработчик событий инфоблока «Заявки»
 *
 * Реагирует на создание и обновление элементов инфоблока «Заявки»,
 * синхронизирует данные с привязанной сделкой CRM.
 *
 * @package SergeyPr\Handlers
 */
class IblockRequestHandler
{
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
     * Флаг для предотвращения рекурсии при обновлении
     *
     * Защищает от бесконечного цикла, когда обновление заявки
     * триггерит обновление сделки, которое снова триггерит обновление заявки.
     * Используется совместно с CrmDealHandler::$disableSync.
     *
     * @var bool
     */
    public static bool $disableSync = false;

    /**
     * Флаг для предотвращения рекурсии внутри класса
     *
     * @var bool
     */
    private static $isUpdating = false;

    /**
     * Обработчик события OnBeforeIBlockElementAdd
     *
     * Устанавливает временное название заявки до сохранения.
     * После получения ID название будет заменено на «Заявка №{ID}».
     *
     * @param array &$arFields Поля элемента (передаются по ссылке)
     * @return bool Всегда true, чтобы не блокировать сохранение
     */
    public static function onBeforeAdd(array &$arFields): bool
    {
        // Проверяем, что это наш инфоблок
        if ((int)$arFields['IBLOCK_ID'] !== self::IBLOCK_ID) {
            return true;
        }

        $arFields['NAME'] = Loc::getMessage('IBLOCK_REQUEST_TEMP_NAME') ?: 'Заявка';

        return true;
    }

    /**
     * Обработчик события OnAfterIBlockElementAdd
     *
     * Выполняется после создания элемента инфоблока:
     * - Обновляет название на «Заявка №{ID}»
     * - Проверяет уникальность привязки сделки
     * - Обновляет сделку данными из заявки
     *
     * @param array $arFields Поля созданного элемента
     * @return void
     */
    public static function onAdd(array $arFields): void
    {
        // Защита от рекурсии и проверка инфоблока
        if (self::$disableSync || (int)$arFields['IBLOCK_ID'] !== self::IBLOCK_ID) {
            return;
        }

        $elementId = (int)$arFields['ID'];

        // Обновляем название на «Заявка №{ID}»
        self::updateRequestName($elementId);

        // Получаем ID привязанной сделки из свойств
        $propertyValues = $arFields['PROPERTY_VALUES'] ?? [];
        $dealProp = $propertyValues[self::PROPERTY_DEAL] ?? [];

        // Значение свойства CRM может быть массивом или строкой вида "D_123"
        $rawDealValue = is_array($dealProp) ? (current($dealProp)['VALUE'] ?? '') : $dealProp;
        $dealId = (int)str_replace('D_', '', $rawDealValue);

        // Если сделка не выбрана — выходим
        if ($dealId <= 0) {
            return;
        }

        // Проверяем, не привязана ли эта сделка к другой заявке
        if (self::isDealLinkedToOtherRequest($dealId, $elementId)) {
            global $APPLICATION;
            $APPLICATION->ThrowException(
                Loc::getMessage('IBLOCK_REQUEST_ERROR_DEAL_EXISTS', ['#NAME#' => self::getRequestName($elementId)])
            );
            return;
        }

        // Обновляем сделку данными из заявки
        self::syncDealFromRequest($elementId);
    }

    /**
     * Обработчик события OnAfterIBlockElementUpdate
     *
     * Выполняется после обновления элемента инфоблока:
     * - Обновляет название на «Заявка №{ID}» (на случай, если оно сбилось)
     * - Проверяет уникальность привязки сделки
     * - Обновляет сделку данными из заявки
     *
     * @param array $arFields Поля обновлённого элемента
     * @return void
     */
    public static function onUpdate(array $arFields): void
    {
        // Защита от рекурсии и проверка инфоблока
        if (self::$disableSync || self::$isUpdating || (int)$arFields['IBLOCK_ID'] !== self::IBLOCK_ID) {
            return;
        }

        self::$isUpdating = true;

        try {
            $elementId = (int)$arFields['ID'];

            // Обновляем название на «Заявка №{ID}»
            self::updateRequestName($elementId);

            // Получаем ID сделки через GetProperty, так как в $arFields['PROPERTY_VALUES']
            // при обновлении могут отсутствовать данные о свойствах
            $dbProp = \CIBlockElement::GetProperty(
                self::IBLOCK_ID,
                $elementId,
                [],
                ['CODE' => self::PROPERTY_DEAL]
            );
            $arProp = $dbProp->Fetch();

            // Очищаем значение от префикса сущности (например, "D_123" → 123)
            $dealId = $arProp ? (int)str_replace('D_', '', $arProp['VALUE']) : 0;

            // Если сделка не выбрана — выходим
            if ($dealId <= 0) {
                return;
            }

            // Проверяем, не привязана ли эта сделка к другой заявке
            if (self::isDealLinkedToOtherRequest($dealId, $elementId)) {
                global $APPLICATION;
                $APPLICATION->ThrowException(
                    Loc::getMessage('IBLOCK_REQUEST_ERROR_DEAL_EXISTS', ['#NAME#' => self::getRequestName($elementId)])
                );
                return;
            }

            // Обновляем сделку данными из заявки
            self::syncDealFromRequest($elementId);

        } finally {
            self::$isUpdating = false;
        }
    }

    /**
     * Проверяет, привязана ли сделка к другой заявке
     *
     * Используется для предотвращения создания нескольких заявок на одну сделку.
     *
     * @param int $dealId ID сделки
     * @param int $currentElementId ID текущей заявки (исключается из проверки)
     * @return bool true — если сделка уже привязана к другой заявке
     */
    protected static function isDealLinkedToOtherRequest(int $dealId, int $currentElementId): bool
    {
        return self::getLinkedRequestId($dealId, $currentElementId) > 0;
    }

    /**
     * Получает ID заявки, привязанной к сделке
     *
     * @param int $dealId ID сделки
     * @param int $currentElementId ID текущей заявки (исключается из проверки)
     * @return int ID найденной заявки или 0
     */
    protected static function getLinkedRequestId(int $dealId, int $currentElementId): int
    {
        $dbResult = \CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => self::IBLOCK_ID,
                '=PROPERTY_' . self::PROPERTY_DEAL => $dealId,
                '!ID' => $currentElementId,
            ],
            false,
            ['nTopCount' => 1],
            ['ID']
        );

        $res = $dbResult->Fetch();
        return $res ? (int)$res['ID'] : 0;
    }

    /**
     * Получает название заявки по ID
     *
     * @param int $elementId ID заявки
     * @return string Название заявки или «Заявка №{ID}», если не найдено
     */
    protected static function getRequestName(int $elementId): string
    {
        $dbResult = \CIBlockElement::GetList(
            [],
            ['ID' => $elementId],
            false,
            ['nTopCount' => 1],
            ['NAME']
        );

        $res = $dbResult->Fetch();
        return $res['NAME'] ?? Loc::getMessage('IBLOCK_REQUEST_NAME_TEMPLATE', ['#ID#' => $elementId]) ?: 'Заявка №' . $elementId;
    }

    /**
     * Обновляет название заявки на «Заявка №{ID}»
     *
     * Проверяет текущее название и обновляет только при необходимости.
     *
     * @param int $elementId ID заявки
     * @return void
     */
    protected static function updateRequestName(int $elementId): void
    {
        // Получаем текущее название
        $dbResult = \CIBlockElement::GetList(
            [],
            ['ID' => $elementId],
            false,
            ['nTopCount' => 1],
            ['NAME']
        );
        $current = $dbResult->Fetch();

        // Формируем правильное название
        $expectedName = Loc::getMessage('IBLOCK_REQUEST_NAME_TEMPLATE', ['#ID#' => $elementId]) ?: 'Заявка №' . $elementId;

        // Если название уже правильное — ничего не делаем
        if ($current && $current['NAME'] === $expectedName) {
            return;
        }

        // Обновляем название
        $el = new \CIBlockElement();
        $el->Update($elementId, ['NAME' => $expectedName], false, false);
    }

    /**
     * Синхронизирует сделку данными из заявки
     *
     * Переносит сумму и ответственного из заявки в привязанную сделку.
     *
     * @param int $elementId ID заявки
     * @return void
     * @throws \Exception Если не удалось обновить сделку
     */
    protected static function syncDealFromRequest(int $elementId): void
    {
        // Подключаем модуль CRM
        if (!Loader::includeModule('crm')) {
            throw new \Exception(Loc::getMessage('IBLOCK_REQUEST_ERROR_CRM_MODULE'));
        }

        // Получаем все свойства заявки одним запросом
        $dealId = 0;
        $sum = 0.0;
        $assignedId = 0;

        $dbProps = \CIBlockElement::GetProperty(self::IBLOCK_ID, $elementId, [], []);
        while ($prop = $dbProps->Fetch()) {
            switch ($prop['CODE']) {
                case self::PROPERTY_DEAL:
                    // Очищаем значение от префикса сущности (например, "D_123" → 123)
                    $dealId = (int)str_replace('D_', '', $prop['VALUE']);
                    break;
                case self::PROPERTY_SUM:
                    $sum = (float)$prop['VALUE'];
                    break;
                case self::PROPERTY_ASSIGNED:
                    $assignedId = (int)$prop['VALUE'];
                    break;
            }
        }

        // Если сделка не выбрана — выходим
        if ($dealId <= 0) {
            return;
        }

        // Получаем фабрику для работы со сделками
        $factory = Container::getInstance()->getFactory(\CCrmOwnerType::Deal);
        if (!$factory) {
            throw new \Exception(Loc::getMessage('IBLOCK_REQUEST_ERROR_DEAL_FACTORY'));
        }

        // Получаем сделку
        $deal = $factory->getItem($dealId);
        if (!$deal) {
            throw new \Exception(Loc::getMessage('IBLOCK_REQUEST_ERROR_DEAL_NOT_FOUND', ['#ID#' => $dealId]));
        }

        $isChanged = false;

        // Обновляем сумму, если она изменилась и больше 0
        if ($sum > 0 && (float)$deal->getOpportunity() !== $sum) {
            $deal->setOpportunity($sum);
            $isChanged = true;
        }

        // Обновляем ответственного, если он изменился и указан
        if ($assignedId > 0 && (int)$deal->getAssignedById() !== $assignedId) {
            $deal->setAssignedById($assignedId);
            $isChanged = true;
        }

        // Если данные не изменились — выходим
        if (!$isChanged) {
            return;
        }

        // Блокируем обработчик сделок, чтобы избежать рекурсии
        if (class_exists('\SergeyPr\Handlers\CrmDealHandler')) {
            \SergeyPr\Handlers\CrmDealHandler::$disableSync = true;
        }

        // Запускаем обновление сделки через фабрику (D7 API)
        $operation = $factory->getUpdateOperation($deal);
        $operation->disableCheckAccess();
        $result = $operation->launch();

        // Разблокируем обработчик сделок
        if (class_exists('\SergeyPr\Handlers\CrmDealHandler')) {
            \SergeyPr\Handlers\CrmDealHandler::$disableSync = false;
        }

        if (!$result->isSuccess()) {
            throw new \Exception(
                Loc::getMessage('IBLOCK_REQUEST_ERROR_DEAL_UPDATE', [
                    '#ERROR#' => implode(', ', $result->getErrorMessages())
                ])
            );
        }
    }
}