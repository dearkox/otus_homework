<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Bizproc\Activity\BaseActivity;
use Bitrix\Bizproc\FieldType;
use Bitrix\Bizproc\Activity\PropertiesDialog;
use Bitrix\Crm\CompanyTable;
use Bitrix\Crm\EntityRequisite;

/**
 * Активити для поиска или создания компании в CRM по ИНН через Dadata
 *
 * @package SergeyPr\Activities
 */
class CBPInnActivity extends BaseActivity
{
    /**
     * Конструктор активити
     *
     * @param string $name Имя активити
     */
    public function __construct($name)
    {
        parent::__construct($name);

        $this->arProperties = [
            'Inn' => '',
            'IblockElementId' => 0,
        ];

        $this->SetPropertiesTypes([
            'CompanyId' => ['Type' => FieldType::INT],
            'CompanyName' => ['Type' => FieldType::STRING],
        ]);
    }

    /**
     * Возвращает путь к файлу активити
     *
     * @return string
     */
    protected static function getFileName(): string
    {
        return __FILE__;
    }

    /**
     * Основное выполнение активити
     *
     * @return \Bitrix\Main\ErrorCollection
     */
    protected function internalExecute(): \Bitrix\Main\ErrorCollection
    {
        $errors = parent::internalExecute();

        if (!Loader::includeModule('crm')) {
            $errors->setError(new \Bitrix\Main\Error('Модуль CRM не установлен'));
            return $errors;
        }

        if (!Loader::includeModule('iblock')) {
            $errors->setError(new \Bitrix\Main\Error('Модуль инфоблоков не установлен'));
            return $errors;
        }

        $inn = trim($this->Inn);

        // Если ИНН пустой — очищаем поле Заказчик и возвращаем ошибку
        if (empty($inn)) {
            if ($this->IblockElementId > 0) {
                $this->clearOrderClient($this->IblockElementId);
            }
            $errors->setError(new \Bitrix\Main\Error('ИНН не указан'));
            return $errors;
        }

        // Токены Dadata
        $token = '56512809ef9c58d8c5cd7e453bb1f11ec4044e5a';
        $secret = '2a47cf7f1f9fe36365b75ee39291e594ed27e973';

        // Запрос к Dadata через curl
        try {
            $url = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party';
            $data = ['query' => $inn, 'count' => 1];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Token ' . $token,
                'X-Secret: ' . $secret,
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new \Exception('HTTP ' . $httpCode);
            }

            $result = json_decode($response, true);
            $suggestions = $result['suggestions'] ?? [];

        } catch (\Exception $e) {
            // При ошибке Dadata — очищаем поле Заказчик
            if ($this->IblockElementId > 0) {
                $this->clearOrderClient($this->IblockElementId);
            }
            $errors->setError(new \Bitrix\Main\Error('Ошибка Dadata: ' . $e->getMessage()));
            return $errors;
        }

        // Если компания не найдена в Dadata — очищаем поле Заказчик и возвращаем ошибку
        if (empty($suggestions)) {
            if ($this->IblockElementId > 0) {
                $this->clearOrderClient($this->IblockElementId);
            }
            $errors->setError(new \Bitrix\Main\Error(
                'Компания с ИНН ' . $inn . ' не найдена в сервисе Dadata. Проверьте правильность ИНН.'
            ));
            return $errors;
        }

        $companyData = $suggestions[0];

        // Ищем компанию по ИНН в реквизитах
        $existingCompanyId = $this->findCompanyByInn($inn);

        if ($existingCompanyId) {
            $companyId = $existingCompanyId;
            $this->updateCompanyRequisite($companyId, $companyData);
            $companyName = $companyData['value'] ?? '';
        } else {
            $companyId = $this->createCompanyWithRequisite($companyData);
            $companyName = $companyData['value'] ?? '';
        }

        if ($companyId && $this->IblockElementId > 0) {
            $this->updateOrderClient($this->IblockElementId, $companyId);
        }

        $this->preparedProperties['CompanyId'] = $companyId;
        $this->preparedProperties['CompanyName'] = $companyName;

        return $errors;
    }

    /**
     * Поиск компании по ИНН в реквизитах
     *
     * @param string $inn ИНН
     * @return int|null ID компании или null
     */
    protected function findCompanyByInn(string $inn): ?int
    {
        $requisite = new EntityRequisite();

        $list = $requisite->getList([
            'filter' => [
                '=ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
                '=RQ_INN' => $inn,
            ],
            'select' => ['ENTITY_ID'],
            'limit' => 1,
        ]);

        if ($row = $list->fetch()) {
            return (int)$row['ENTITY_ID'];
        }

        return null;
    }

    /**
     * Создание компании с реквизитами
     *
     * @param array $companyData Данные из Dadata
     * @return int ID созданной компании
     */
    protected function createCompanyWithRequisite(array $companyData): int
    {
        global $USER;

        $userId = $USER->GetID() ?: 1;

        $companyFields = [
            'TITLE' => $companyData['value'] ?? 'Компания',
            'OPENED' => 'Y',
            'IS_MY_COMPANY' => 'N',
            'CREATED_BY_ID' => $userId,
            'ASSIGNED_BY_ID' => $userId,
            'MODIFY_BY_ID' => $userId,
        ];

        if (!empty($companyData['data']['address']['value'])) {
            $companyFields['ADDRESS'] = $companyData['data']['address']['value'];
        }

        if (!empty($companyData['data']['phones'][0]['value'])) {
            $companyFields['PHONE'] = $companyData['data']['phones'][0]['value'];
        }

        $result = CompanyTable::add($companyFields);
        if (!$result->isSuccess()) {
            throw new \Exception('Ошибка создания компании: ' . implode(', ', $result->getErrorMessages()));
        }

        $companyId = $result->getId();

        $this->createRequisite($companyId, $companyData);

        return $companyId;
    }

    /**
     * Создание реквизитов для компании
     *
     * @param int $companyId ID компании
     * @param array $companyData Данные из Dadata
     * @return int ID реквизита
     */
    protected function createRequisite(int $companyId, array $companyData): int
    {
        $requisite = new EntityRequisite();

        $fields = [
            'ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
            'ENTITY_ID' => $companyId,
            'PRESET_ID' => 1,
            'NAME' => 'Основные реквизиты',
            'ACTIVE' => 'Y',
            'RQ_INN' => $this->Inn,
            'RQ_COMPANY_NAME' => $companyData['value'] ?? '',
            'RQ_KPP' => $companyData['data']['kpp'] ?? '',
            'RQ_OGRN' => $companyData['data']['ogrn'] ?? '',
            'RQ_ADDR' => $companyData['data']['address']['value'] ?? '',
        ];

        $result = $requisite->add($fields);
        if (!$result->isSuccess()) {
            throw new \Exception('Ошибка создания реквизитов: ' . implode(', ', $result->getErrorMessages()));
        }

        return $result->getId();
    }

    /**
     * Обновление реквизитов компании
     *
     * @param int $companyId ID компании
     * @param array $companyData Данные из Dadata
     * @return void
     */
    protected function updateCompanyRequisite(int $companyId, array $companyData): void
    {
        $requisite = new EntityRequisite();

        $list = $requisite->getList([
            'filter' => [
                '=ENTITY_TYPE_ID' => \CCrmOwnerType::Company,
                '=ENTITY_ID' => $companyId,
            ],
            'select' => ['ID'],
            'limit' => 1,
        ]);

        if ($row = $list->fetch()) {
            $fields = [
                'RQ_COMPANY_NAME' => $companyData['value'] ?? '',
                'RQ_KPP' => $companyData['data']['kpp'] ?? '',
                'RQ_OGRN' => $companyData['data']['ogrn'] ?? '',
                'RQ_ADDR' => $companyData['data']['address']['value'] ?? '',
            ];

            $fields = array_filter($fields);

            if (!empty($fields)) {
                $result = $requisite->update($row['ID'], $fields);
                if (!$result->isSuccess()) {
                    throw new \Exception('Ошибка обновления реквизитов: ' . implode(', ', $result->getErrorMessages()));
                }
            }
        } else {
            $this->createRequisite($companyId, $companyData);
        }

        $companyFields = [
            'TITLE' => $companyData['value'] ?? '',
            'ADDRESS' => $companyData['data']['address']['value'] ?? '',
            'PHONE' => $companyData['data']['phones'][0]['value'] ?? '',
        ];

        $companyFields = array_filter($companyFields);

        if (!empty($companyFields)) {
            $result = CompanyTable::update($companyId, $companyFields);
            if (!$result->isSuccess()) {
                throw new \Exception('Ошибка обновления компании: ' . implode(', ', $result->getErrorMessages()));
            }
        }
    }

    /**
     * Очистка поля Заказчик в элементе инфоблока
     *
     * @param int $elementId ID элемента инфоблока
     * @return void
     */
    protected function clearOrderClient(int $elementId): void
    {
        \CIBlockElement::SetPropertyValuesEx(
            $elementId,
            21,
            ['CLIENT' => '']
        );
    }

    /**
     * Обновление поля Заказчик в элементе инфоблока
     *
     * @param int $elementId ID элемента инфоблока
     * @param int $companyId ID компании
     * @return void
     */
    protected function updateOrderClient(int $elementId, int $companyId): void
    {
        \CIBlockElement::SetPropertyValuesEx(
            $elementId,
            21,
            ['CLIENT' => $companyId]
        );
    }

    /**
     * Карта полей для диалога свойств активити
     *
     * @param PropertiesDialog|null $dialog
     * @return array[]
     */
    public static function getPropertiesDialogMap(?PropertiesDialog $dialog = null): array
    {
        return [
            'Inn' => [
                'Name' => 'ИНН',
                'FieldName' => 'inn',
                'Type' => FieldType::STRING,
                'Required' => true,
            ],
            'IblockElementId' => [
                'Name' => 'ID элемента инфоблока',
                'FieldName' => 'iblock_element_id',
                'Type' => FieldType::INT,
                'Required' => false,
            ],
        ];
    }
}