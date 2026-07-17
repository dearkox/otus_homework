<?php

/**
 * AJAX-обработчик для создания бронирования
 *
 * Принимает POST-данные из модального окна:
 * - patientName (string) — ФИО пациента
 * - procedureId (int) — ID процедуры
 * - doctorId (int) — ID врача
 * - datetime (string) — Дата и время в формате YYYY-MM-DD HH:MM:SS
 *
 * Возвращает JSON:
 * - status (string) — 'success' или 'error'
 * - message (string) — сообщение об ошибке
 * - bookingId (int) — ID созданной записи (при успехе)
 */

use Bitrix\Main\Loader;
use SergeyPr\Booking\BookingManager;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

// Проверка авторизации
global $USER;
if (!$USER->IsAuthorized()) {
    echo json_encode(['status' => 'error', 'message' => 'Не авторизован']);
    die();
}

// Проверка POST-запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Неверный метод запроса']);
    die();
}

// Получение данных
$patientName = trim($_POST['patientName'] ?? '');
$procedureId = (int)($_POST['procedureId'] ?? 0);
$doctorId = (int)($_POST['doctorId'] ?? 0);
$datetime = trim($_POST['datetime'] ?? '');

// Валидация
if (empty($patientName)) {
    echo json_encode(['status' => 'error', 'message' => 'Введите ФИО пациента']);
    die();
}

if ($procedureId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Не указана процедура']);
    die();
}

if ($doctorId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Не указан врач']);
    die();
}

if (empty($datetime)) {
    echo json_encode(['status' => 'error', 'message' => 'Выберите дату и время']);
    die();
}

// Проверка формата даты
if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $datetime)) {
    echo json_encode(['status' => 'error', 'message' => 'Неверный формат даты и времени (требуется YYYY-MM-DD HH:MM:SS)']);
    die();
}

// Загрузка модуля iblock
if (!Loader::includeModule('iblock')) {
    echo json_encode(['status' => 'error', 'message' => 'Модуль iblock не загружен']);
    die();
}

// Проверка на занятость
if (BookingManager::isTimeSlotTaken($procedureId, $datetime)) {
    echo json_encode(['status' => 'error', 'message' => 'Это время уже занято']);
    die();
}

// Создание бронирования
$result = BookingManager::addBooking($patientName, $procedureId, $doctorId, $datetime);

if ($result === false) {
    echo json_encode(['status' => 'error', 'message' => 'Не удалось создать бронирование']);
    die();
}

echo json_encode(['status' => 'success', 'bookingId' => $result]);
die();