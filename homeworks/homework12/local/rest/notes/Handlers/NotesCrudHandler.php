<?php

namespace SergeyPr\Rest\Notes\Handlers;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;
use Bitrix\Rest\RestException;
use Bitrix\Main\Diag\Debug;
use SergeyPr\Rest\Notes\ORM\NotesTable;

/**
 * Обработчики CRUD-методов для REST API сущности "Заметки"
 *
 * Реализует операции:
 * - add — создание заметки
 * - update — обновление заметки
 * - delete — удаление заметки
 * - get — получение одной заметки
 * - list — получение списка с фильтрацией и пагинацией
 *
 * @package SergeyPr\Rest\Notes\Handlers
 */
class NotesCrudHandler extends \IRestService
{
    /**
     * Лимит записей на страницу для списочного метода
     */
    public const LIST_LIMIT = 50;

    /**
     * Логирует данные в файл
     *
     * @param string $message Сообщение для лога
     * @param array $data Данные для логирования
     * @return void
     */
    private static function log(string $message, array $data = []): void
    {
        $logData = $message . (!empty($data) ? ' | Данные: ' . print_r($data, true) : '');
        Debug::writeToFile($logData, 'REST_NOTES', '/local/logs/rest.log');
    }

    /**
     * Создание новой заметки
     *
     * @param array $params Параметры запроса (fields)
     * @param int $start Параметр для пагинации (не используется)
     * @param \CRestServer $server Объект сервера
     * @return int ID созданной заметки
     * @throws RestException
     */
    public static function add(array $params, int $start, \CRestServer $server): int
    {
        global $USER;

        // Логируем входящие данные
        self::log('add вызван', $params);

        // Проверяем обязательное поле TITLE
        if (empty($params['fields']['TITLE'])) {
            self::log('add ошибка: TITLE обязателен');
            throw new RestException(
                Loc::getMessage('OTUS_NOTES_ERROR_TITLE_REQUIRED'),
                'TITLE_REQUIRED'
            );
        }

        // Подготавливаем данные для вставки
        $fields = [
            'TITLE' => trim($params['fields']['TITLE']),
            'TEXT' => $params['fields']['TEXT'] ?? '',
            'CREATED_BY' => $USER->GetID() ?: 1,
            'CREATED_AT' => new DateTime(),
            'UPDATED_AT' => new DateTime(),
        ];

        // Выполняем вставку
        $result = NotesTable::add($fields);

        if (!$result->isSuccess()) {
            $errors = implode(', ', $result->getErrorMessages());
            self::log('add ошибка: ' . $errors);
            throw new RestException($errors, 'ADD_ERROR');
        }

        $id = $result->getId();
        self::log('add успешно, ID: ' . $id);

        return $id;
    }

    /**
     * Обновление заметки
     *
     * @param array $params Параметры запроса (fields)
     * @param int $start Параметр для пагинации (не используется)
     * @param \CRestServer $server Объект сервера
     * @return bool
     * @throws RestException
     */
    public static function update(array $params, int $start, \CRestServer $server): bool
    {
        // Логируем входящие данные
        self::log('update вызван', $params);

        // Проверяем наличие ID
        if (empty($params['fields']['ID'])) {
            self::log('update ошибка: ID не указан');
            throw new RestException(
                Loc::getMessage('OTUS_NOTES_ERROR_ID_REQUIRED'),
                'ID_REQUIRED'
            );
        }

        $id = (int)$params['fields']['ID'];

        // Проверяем существование записи
        $existing = NotesTable::getList([
            'select' => ['ID'],
            'filter' => ['=ID' => $id],
            'limit' => 1,
        ])->fetch();

        if (!$existing) {
            self::log('update ошибка: запись не найдена, ID: ' . $id);
            throw new RestException(
                Loc::getMessage('OTUS_NOTES_ERROR_NOT_FOUND', ['#ID#' => $id]),
                'NOT_FOUND'
            );
        }

        // Подготавливаем данные для обновления
        $fields = [
            'UPDATED_AT' => new DateTime(),
        ];

        if (isset($params['fields']['TITLE'])) {
            $fields['TITLE'] = trim($params['fields']['TITLE']);
        }

        if (isset($params['fields']['TEXT'])) {
            $fields['TEXT'] = $params['fields']['TEXT'];
        }

        // Выполняем обновление
        $result = NotesTable::update($id, $fields);

        if (!$result->isSuccess()) {
            $errors = implode(', ', $result->getErrorMessages());
            self::log('update ошибка: ' . $errors);
            throw new RestException($errors, 'UPDATE_ERROR');
        }

        self::log('update успешно, ID: ' . $id);
        return true;
    }

    /**
     * Удаление заметки
     *
     * @param array $params Параметры запроса (id)
     * @param int $start Параметр для пагинации (не используется)
     * @param \CRestServer $server Объект сервера
     * @return bool
     * @throws RestException
     */
    public static function delete(array $params, int $start, \CRestServer $server): bool
    {
        // Логируем входящие данные
        self::log('delete вызван', $params);

        // Проверяем наличие ID
        if (empty($params['id'])) {
            self::log('delete ошибка: ID не указан');
            throw new RestException(
                Loc::getMessage('OTUS_NOTES_ERROR_ID_REQUIRED'),
                'ID_REQUIRED'
            );
        }

        $id = (int)$params['id'];

        // Проверяем существование записи
        $existing = NotesTable::getList([
            'select' => ['ID'],
            'filter' => ['=ID' => $id],
            'limit' => 1,
        ])->fetch();

        if (!$existing) {
            self::log('delete ошибка: запись не найдена, ID: ' . $id);
            throw new RestException(
                Loc::getMessage('OTUS_NOTES_ERROR_NOT_FOUND', ['#ID#' => $id]),
                'NOT_FOUND'
            );
        }

        // Выполняем удаление
        $result = NotesTable::delete($id);

        if (!$result->isSuccess()) {
            $errors = implode(', ', $result->getErrorMessages());
            self::log('delete ошибка: ' . $errors);
            throw new RestException($errors, 'DELETE_ERROR');
        }

        self::log('delete успешно, ID: ' . $id);
        return true;
    }

    /**
     * Получение одной заметки по ID
     *
     * @param array $params Параметры запроса (id)
     * @param int $start Параметр для пагинации (не используется)
     * @param \CRestServer $server Объект сервера
     * @return array
     * @throws RestException
     */
    public static function get(array $params, int $start, \CRestServer $server): array
    {
        // Логируем входящие данные
        self::log('get вызван', $params);

        // Проверяем наличие ID
        if (empty($params['id'])) {
            self::log('get ошибка: ID не указан');
            throw new RestException(
                Loc::getMessage('OTUS_NOTES_ERROR_ID_REQUIRED'),
                'ID_REQUIRED'
            );
        }

        $id = (int)$params['id'];

        // Получаем запись
        $result = NotesTable::getList([
            'select' => ['*'],
            'filter' => ['=ID' => $id],
            'limit' => 1,
        ])->fetch();

        if (!$result) {
            self::log('get ошибка: запись не найдена, ID: ' . $id);
            throw new RestException(
                Loc::getMessage('OTUS_NOTES_ERROR_NOT_FOUND', ['#ID#' => $id]),
                'NOT_FOUND'
            );
        }

        // Форматируем даты в ISO 8601
        $result['CREATED_AT'] = self::formatDate($result['CREATED_AT']);
        $result['UPDATED_AT'] = self::formatDate($result['UPDATED_AT']);

        self::log('get успешно, ID: ' . $id);
        return $result;
    }

    /**
     * Получение списка заметок с фильтрацией и пагинацией
     *
     * @param array $params Параметры запроса (filter, order, start)
     * @param int $start Параметр для пагинации
     * @param \CRestServer $server Объект сервера
     * @return array
     */
    public static function list(array $params, int $start, \CRestServer $server): array
    {
        // Логируем входящие данные
        self::log('list вызван', $params);

        $filter = $params['filter'] ?? [];
        $order = $params['order'] ?? ['ID' => 'ASC'];

        // Настройка пагинации
        $navParams = self::getNavData($start, true);
        $limit = $navParams['limit'] ?? self::LIST_LIMIT;
        $offset = $navParams['offset'] ?? 0;

        // Получаем общее количество записей
        $totalCount = NotesTable::getCount($filter);

        // Получаем данные
        $items = NotesTable::getList([
            'select' => ['*'],
            'filter' => $filter,
            'order' => $order,
            'limit' => $limit,
            'offset' => $offset,
        ])->fetchAll();

        // Форматируем даты
        foreach ($items as &$item) {
            $item['CREATED_AT'] = self::formatDate($item['CREATED_AT']);
            $item['UPDATED_AT'] = self::formatDate($item['UPDATED_AT']);
        }

        // Формируем результат с пагинацией
        $result = $items;

        if ($offset + $limit < $totalCount) {
            $result['next'] = $offset + $limit;
        }
        $result['total'] = $totalCount;

        self::log('list успешно, количество: ' . count($items));
        return $result;
    }

    /**
     * Форматирует дату в ISO 8601 для REST API
     *
     * @param mixed $date
     * @return string|null
     */
    private static function formatDate($date): ?string
    {
        if ($date instanceof DateTime) {
            return $date->format('Y-m-d\TH:i:sP');
        }
        return $date;
    }
}