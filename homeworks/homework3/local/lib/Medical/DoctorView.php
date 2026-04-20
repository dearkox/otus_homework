<?php

namespace SergeyPr\Medical;

use Bitrix\Main\Loader;
use Bitrix\Iblock\Elements\ElementDoctorsTable;
use Bitrix\Iblock\Elements\ElementProceduresTable;

Loader::includeModule('iblock');

/**
 * Класс для вывода данных о врачах (список, детальная карточка, получение данных)
 */
class DoctorView
{
    /**
     * Возвращает список всех врачей для отображения в общем списке
     *
     * @return array Массив врачей с ключами ID, NAME, CODE
     */
    public static function getAll(): array
    {
        return ElementDoctorsTable::getList([
            'select' => ['ID', 'NAME', 'CODE'],
            'filter' => ['=ACTIVE' => 'Y'],
            'order' => ['NAME' => 'ASC']
        ])->fetchAll();
    }

    /**
     * Возвращает данные врача по символьному коду с использованием коллекции
     *
     * @param string $code Символьный код врача
     * @return array|null Массив с данными врача или null, если не найден
     */
    public static function getByCode(string $code): ?array
    {
        $doctors = ElementDoctorsTable::getList([
            'select' => [
                'ID',
                'NAME',
                'CODE',
                'LAST_NAME',
                'FIRST_NAME',
                'MIDDLE_NAME',
                'PROCEDURES.ELEMENT'  // Получаем связанные элементы процедур
            ],
            'filter' => [
                '=CODE' => $code,
                '=ACTIVE' => 'Y'
            ]
        ])->fetchCollection();

        if ($doctors->isEmpty()) {
            return null;
        }

        $doctor = $doctors->getAll()[0];

        // Собираем ID процедур из коллекции
        $procedureIds = [];
        $proceduresCollection = $doctor->get('PROCEDURES');

        if ($proceduresCollection) {
            foreach ($proceduresCollection->getAll() as $procedureItem) {
                $procedureElement = $procedureItem->get('ELEMENT');
                if ($procedureElement) {
                    $procedureIds[] = $procedureElement->getId();
                }
            }
        }

        return [
            'ID' => $doctor->getId(),
            'NAME' => $doctor->getName(),
            'CODE' => $doctor->getCode(),
            'LAST_NAME_VALUE' => $doctor->get('LAST_NAME') ? $doctor->get('LAST_NAME')->getValue() : '',
            'FIRST_NAME_VALUE' => $doctor->get('FIRST_NAME') ? $doctor->get('FIRST_NAME')->getValue() : '',
            'MIDDLE_NAME_VALUE' => $doctor->get('MIDDLE_NAME') ? $doctor->get('MIDDLE_NAME')->getValue() : '',
            'PROCEDURES_VALUE' => $procedureIds
        ];
    }

    /**
     * Возвращает список процедур для указанного врача
     *
     * @param string $code Символьный код врача
     * @return array Массив процедур с ключами ID, NAME, PROCEDURE
     */
    public static function getProceduresByDoctorCode(string $code): array
    {
        $doctor = self::getByCode($code);

        if (!$doctor || empty($doctor['PROCEDURES_VALUE'])) {
            return [];
        }

        $procedureIds = $doctor['PROCEDURES_VALUE']; // Для лучшей читаемости кода

        return ElementProceduresTable::getList([
            'select' => ['ID', 'NAME', 'PROCEDURE'],
            'filter' => [
                '=ID' => $procedureIds,
                '=ACTIVE' => 'Y'
            ]
        ])->fetchAll();
    }

    /**
     * Возвращает HTML-список всех врачей (плашки)
     *
     * @return string HTML-код списка врачей
     */
    public static function renderList(): string
    {
        $doctors = self::getAll();

        if (empty($doctors)) {
            return '<p>Врачи не найдены</p>';
        }

        $html = '<div class="doctors-grid">';
        foreach ($doctors as $doctor) {
            $html .= sprintf(
                '<div class="doctor-card">
                    <a href="/doctors/%s">
                        <h3>%s</h3>
                    </a>
                </div>',
                htmlspecialchars($doctor['CODE']),
                htmlspecialchars($doctor['NAME'])
            );
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Возвращает HTML-карточку врача с его процедурами
     *
     * @param string $code Символьный код врача
     * @return string HTML-код карточки врача
     */
    public static function renderDetail(string $code): string
    {
        $doctor = self::getByCode($code);

        if (!$doctor) {
            return '<p>Врач не найден</p>';
        }

        // Используем NAME для вывода ФИО
        $fullName = $doctor['NAME'] ?? 'Без имени';

        $procedures = self::getProceduresByDoctorCode($code);

        $html = '<div class="doctor-detail">';
        $html .= '<h2>' . htmlspecialchars($fullName) . '</h2>';

        if (!empty($procedures)) {
            $html .= '<h3>Процедуры:</h3><ul>';
            foreach ($procedures as $procedure) {
                $procedureName = $procedure['PROCEDURE'] ?: $procedure['NAME'];
                $html .= '<li>' . htmlspecialchars($procedureName) . '</li>';
            }
            $html .= '</ul>';
        } else {
            $html .= '<p>Процедуры не назначены</p>';
        }

        $html .= '</div>';

        return $html;
    }
}