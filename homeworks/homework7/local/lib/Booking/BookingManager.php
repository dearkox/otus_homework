<?php

namespace SergeyPr\Booking;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use CIBlockElement;

/**
 * Класс для управления бронированием
 */
class BookingManager
{
    /**
     * ID инфоблока "Бронирование"
     */
    private const IBLOCK_ID = 20;

    /**
     * Создаёт новое бронирование
     *
     * @param string $patientName ФИО пациента
     * @param int $procedureId ID процедуры
     * @param int $doctorId ID врача
     * @param string $dateTime Дата и время в формате YYYY-MM-DD HH:MM:SS
     * @return int|false ID созданного элемента или false
     * @throws LoaderException
     */
    public static function addBooking(string $patientName, int $procedureId, int $doctorId, string $dateTime): false|int
    {
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/debug_booking.log',
            "BookingManager::addBooking called\n",
            FILE_APPEND
        );

        if (!Loader::includeModule('iblock')) {
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/debug_booking.log',
                "ERROR: iblock module not loaded in BookingManager\n",
                FILE_APPEND
            );
            throw new LoaderException('Модуль iblock не загружен');
        }

        if (self::isTimeSlotTaken($procedureId, $dateTime)) {
            file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/debug_booking.log',
                "ERROR: Time slot taken\n",
                FILE_APPEND
            );
            return false;
        }

        $element = new CIBlockElement();
        $fields = [
            'IBLOCK_ID' => self::IBLOCK_ID,
            'NAME' => $patientName . ' — ' . $dateTime,
            'ACTIVE' => 'Y',
            'PROPERTY_VALUES' => [
                'PATIENT_NAME' => $patientName,
                'PROCEDURE_ID' => $procedureId,
                'DOCTOR_ID' => $doctorId,
                'BOOKING_DATETIME' => $dateTime,
            ],
        ];

        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/debug_booking.log',
            "Fields: " . print_r($fields, true) . "\n",
            FILE_APPEND
        );

        $result = $element->Add($fields);
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/debug_booking.log',
            "CIBlockElement::Add result: " . ($result ? $result : 'false') . "\n",
            FILE_APPEND
        );

        return $result;
    }

    /**
     * Проверяет, занят ли временной слот
     *
     * @param int $procedureId ID процедуры
     * @param string $dateTime Дата и время
     * @return bool
     */
    public static function isTimeSlotTaken(int $procedureId, string $dateTime): bool
    {
        $filter = [
            'IBLOCK_ID' => self::IBLOCK_ID,
            'PROPERTY_PROCEDURE_ID' => $procedureId,
            'PROPERTY_BOOKING_DATETIME' => $dateTime,
        ];

        $res = CIBlockElement::GetList([], $filter, false, ['nTopCount' => 1], ['ID']);
        return (bool)$res->Fetch();
    }
}