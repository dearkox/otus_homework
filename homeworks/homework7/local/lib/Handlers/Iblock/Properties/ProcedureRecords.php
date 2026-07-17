<?php

namespace SergeyPr\Handlers\Iblock\Properties;

use Bitrix\Main\Loader;
use CIBlockElement;

/**
 * Класс пользовательского свойства "Запись на процедуры" для инфоблоков
 *
 * Реализует тип свойства, который отображает список процедур, привязанных к врачу,
 * и позволяет создать бронирование через модальное окно.
 *
 * @package SergeyPr\Handlers\Iblock\Properties
 */
class ProcedureRecords
{
    /**
     * Возвращает описание пользовательского типа свойства
     *
     * @return array Массив с описанием типа свойства и методами-обработчиками
     */
    public static function GetUserTypeDescription(): array
    {
        return [
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => 'ProcedureRecords',
            'DESCRIPTION' => 'Запись на процедуры',
            'GetPropertyFieldHtml' => [__CLASS__, 'GetPropertyFieldHtml'],
            'GetPublicViewHTML' => [__CLASS__, 'GetPublicViewHTML'],
            'GetAdminListViewHTML' => [__CLASS__, 'GetAdminListViewHTML'],
            'GetAdminListName' => [__CLASS__, 'GetAdminListName'],
            'CheckFields' => [__CLASS__, 'CheckFields'],
        ];
    }

    /**
     * Отрисовка поля в форме редактирования (админка)
     */
    public static function GetPropertyFieldHtml(array $arProperty, array $value, array $strHTMLControlName): string
    {
        global $APPLICATION;

        if (!Loader::includeModule('iblock')) {
            return '<span class="error">Модуль iblock не загружен</span>';
        }

        $APPLICATION->AddHeadScript('/local/js/ProcedureRecords/script.js');

        $elementId = (int)($arProperty['IBLOCK_ELEMENT_ID'] ?? 0);
        if ($elementId <= 0) {
            return '<span class="error">Не удалось определить врача</span>';
        }

        $procedureIds = [];
        $propertyRes = CIBlockElement::GetProperty(
            (int)$arProperty['IBLOCK_ID'],
            $elementId,
            [],
            ['CODE' => 'PROCEDURES']
        );
        while ($prop = $propertyRes->Fetch()) {
            if (!empty($prop['VALUE'])) {
                $procedureIds[] = (int)$prop['VALUE'];
            }
        }

        if (empty($procedureIds)) {
            return '<span class="empty">У этого врача пока нет процедур</span>';
        }

        $procedureList = [];
        $res = CIBlockElement::GetList(
            ['NAME' => 'ASC'],
            ['IBLOCK_ID' => 17, 'ID' => $procedureIds, 'ACTIVE' => 'Y'],
            false,
            false,
            ['ID', 'NAME']
        );
        while ($item = $res->Fetch()) {
            $procedureList[$item['ID']] = $item['NAME'];
        }

        $html = '<div class="procedure-records-wrapper"><ul style="list-style: none; padding: 0; margin: 0;">';
        foreach ($procedureList as $id => $name) {
            $html .= sprintf(
                '<li style="margin-bottom: 8px;">
                    <a href="#"
                       class="procedure-record-link"
                       data-procedure-id="%d"
                       data-doctor-id="%d"
                       data-procedure-name="%s"
                       style="color: #1e8ec7; text-decoration: underline; cursor: pointer;">
                       %s
                    </a>
                </li>',
                $id,
                $elementId,
                htmlspecialcharsbx($name),
                htmlspecialcharsbx($name)
            );
        }
        $html .= '</ul></div>';

        $html .= sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialcharsbx($strHTMLControlName['VALUE']),
            htmlspecialcharsbx($value['VALUE'] ?? '')
        );

        return $html;
    }

    /**
     * Отрисовка значения в публичной части (список элементов)
     *
     * Выводит список процедур в виде кликабельных ссылок,
     * которые открывают модальное окно для бронирования.
     */
    public static function GetPublicViewHTML($arProperty, $value, $strHTMLControlName)
    {
        if (!Loader::includeModule('iblock')) {
            return '&nbsp;';
        }

        $elementId = (int)($arProperty['ELEMENT_ID'] ?? 0);
        if ($elementId <= 0) {
            return '&nbsp;';
        }

        $procedureIds = [];
        $propertyRes = CIBlockElement::GetProperty(
            (int)$arProperty['IBLOCK_ID'],
            $elementId,
            [],
            ['CODE' => 'PROCEDURES']
        );
        while ($prop = $propertyRes->Fetch()) {
            if (!empty($prop['VALUE'])) {
                $procedureIds[] = (int)$prop['VALUE'];
            }
        }

        if (empty($procedureIds)) {
            return '&nbsp;';
        }

        $procedureList = [];
        $res = CIBlockElement::GetList(
            ['NAME' => 'ASC'],
            ['IBLOCK_ID' => 17, 'ID' => $procedureIds, 'ACTIVE' => 'Y'],
            false,
            false,
            ['ID', 'NAME']
        );
        while ($item = $res->Fetch()) {
            $procedureList[$item['ID']] = $item['NAME'];
        }

        $html = '<div class="procedure-records-public"><ul style="list-style: none; padding: 0; margin: 0;">';
        foreach ($procedureList as $id => $name) {
            $html .= sprintf(
                '<li style="margin-bottom: 5px;">
                    <a href="#"
                       class="procedure-record-link"
                       data-procedure-id="%d"
                       data-doctor-id="%d"
                       data-procedure-name="%s"
                       style="color: #1e8ec7; text-decoration: underline; cursor: pointer;">
                       %s
                    </a>
                </li>',
                $id,
                $elementId,
                htmlspecialcharsbx($name),
                htmlspecialcharsbx($name)
            );
        }
        $html .= '</ul></div>';

        global $APPLICATION;
        $APPLICATION->AddHeadScript('/local/js/ProcedureRecords/script.js');

        return $html;
    }

    /**
     * Отрисовка значения в административном списке
     */
    public static function GetAdminListViewHTML(array $arProperty, array $value, array $strHTMLControlName): string
    {
        return '&nbsp;';
    }

    /**
     * Получение названия значения для отображения в списке
     */
    public static function GetAdminListName(array $arProperty, string $value): string
    {
        return (string)$value;
    }

    /**
     * Проверка корректности введённого значения
     */
    public static function CheckFields(array $arProperty, mixed $value): string
    {
        if (is_string($value) && strlen($value) > 255) {
            return 'Значение не должно превышать 255 символов';
        }

        return '';
    }
}