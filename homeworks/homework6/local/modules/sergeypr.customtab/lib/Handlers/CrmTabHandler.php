<?php

namespace SergeyPr\CustomTab\Handlers;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

/**
 * Обработчик события onEntityDetailsTabsInitialized
 *
 * Добавляет кастомную вкладку "Свои заметки" в карточки всех CRM-сущностей
 */
class CrmTabHandler
{
    /**
     * Точка входа для события onEntityDetailsTabsInitialized
     *
     * @param Event $event Событие, содержащее информацию о сущности и вкладках
     * @return EventResult
     */
    public static function onEntityDetailsTabsInitialized(Event $event): EventResult
    {
        // Подключаем модуль для автозагрузки классов
        if (!Loader::includeModule('sergeypr.customtab')) {
            return new EventResult(EventResult::SUCCESS);
        }

        // Подключаем языковой файл
        Loc::loadMessages(__FILE__);

        // Получаем параметры события
        $tabs = $event->getParameter('tabs');
        $entityID = $event->getParameter('entityID');
        $entityTypeID = $event->getParameter('entityTypeID');

        // Уникальный идентификатор вкладки
        $tabId = 'sergeypr_custom_tab';

        // Проверяем, не добавлена ли уже такая вкладка
        foreach ($tabs as $tab) {
            if (isset($tab['id']) && $tab['id'] === $tabId) {
                return new EventResult(EventResult::SUCCESS);
            }
        }

        // Название вкладки
        $tabName = Loc::getMessage('SERGEYPR_CUSTOMTAB_TAB_NAME') ?: 'Свои заметки';

        // Добавляем вкладку с ленивой загрузкой
        $tabs[] = [
            'id' => $tabId,
            'name' => $tabName,
            'loader' => [
                'serviceUrl' => '/local/components/sergeypr/crm.notes.grid/lazyload.ajax.php',
                'componentData' => [
                    'componentName' => 'sergeypr:crm.notes.grid',
                    'template' => '',
                    'params' => [
                        'ENTITY_TYPE' => self::getEntityTypeName($entityTypeID),
                        'ENTITY_ID' => $entityID,
                    ],
                ],
            ],
        ];

        return new EventResult(EventResult::SUCCESS, [
            'tabs' => $tabs,
        ]);
    }

    /**
     * Преобразует числовой идентификатор типа CRM-сущности в строковое имя
     *
     * @param int $entityTypeId
     * @return string
     */
    protected static function getEntityTypeName(int $entityTypeId): string
    {
        // Маппинг ID типов сущностей (константы CCrmOwnerType)
        $map = [
            \CCrmOwnerType::Deal => 'DEAL',
            \CCrmOwnerType::Lead => 'LEAD',
            \CCrmOwnerType::Contact => 'CONTACT',
            \CCrmOwnerType::Company => 'COMPANY',
        ];

        return $map[$entityTypeId] ?? 'DEAL';
    }
}