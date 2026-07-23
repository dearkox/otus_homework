<?php
/**
 * CRest - класс для работы с REST API Битрикс24
 *
 * Оптимизированная версия для локальных приложений.
 * Автоматически собирает авторизационные данные из разных источников.
 *
 * @package CRest
 * @version 1.1
 */

class CRest
{
    /**
     * Выполняет запрос к REST API Битрикс24
     *
     * @param string $method Название REST-метода (например, crm.contact.update)
     * @param array $params Параметры запроса
     * @return mixed Массив с результатом или ошибкой
     */
    public static function call($method, $params = [])
    {
        /**
         * Собираем токен авторизации из любых возможных источников.
         *
         * Битрикс передаёт токены в разных форматах:
         * - В обработчике событий: $_REQUEST['auth']['access_token']
         * - При установке приложения: $_REQUEST['AUTH_ID'] или $_REQUEST['auth_id']
         */
        $accessToken = $_REQUEST['auth']['access_token']
            ?? $_REQUEST['AUTH_ID']
            ?? $_REQUEST['auth_id']
            ?? '';

        // Если токен получен — добавляем в параметры запроса
        if (!empty($accessToken)) {
            $params['auth'] = $accessToken;
        }

        /**
         * Универсально определяем домен портала.
         *
         * Проверяем несколько вариантов:
         * - Внутри массива auth
         * - Отдельный параметр DOMAIN (установка приложения)
         * - Отдельный параметр domain
         */
        $domain = $_REQUEST['auth']['domain']
            ?? $_REQUEST['DOMAIN']
            ?? $_REQUEST['domain']
            ?? '';

        // Если домен или токен отсутствуют — возвращаем ошибку
        if (empty($domain) || empty($accessToken)) {
            return ['error' => 'no_auth_data', 'error_description' => 'Отсутствуют токены авторизации'];
        }

        // Формируем URL запроса
        $url = 'https://' . $domain . '/rest/' . $method;

        // Выполняем POST-запрос через cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);                                // URL REST-метода
        curl_setopt($ch, CURLOPT_POST, true);                         // Используем метод POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));    // Передаём параметры в теле запроса
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);               // Возвращать результат, а не выводить сразу
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);              // Отключаем проверку SSL-сертификата
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);              // Отключаем проверку имени хоста в SSL-сертификате
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);                        // Таймаут выполнения запроса в секундах

        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        // Обрабатываем ошибки cURL
        if ($error) {
            return ['error' => 'curl_error', 'error_description' => $error];
        }

        // Декодируем JSON-ответ в массив
        return json_decode($result, true);
    }
}