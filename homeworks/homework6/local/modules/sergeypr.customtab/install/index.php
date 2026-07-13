<?php

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use SergeyPr\CustomTab\ORM\CrmNotesTable;

Loc::loadMessages(__FILE__);

// Явное подключение ORM-класса для установки (до регистрации модуля)
require_once __DIR__ . '/../lib/ORM/CrmNotesTable.php';

/**
 * Класс установки/удаления модуля sergeypr.customtab
 */
class sergeypr_customtab extends CModule
{
    /**
     * Конструктор модуля
     *
     * Заполняет основные свойства модуля: ID, версия, название, описание
     */
    public function __construct()
    {
        $this->MODULE_ID = 'sergeypr.customtab';
        $this->MODULE_VERSION = '1.0.0';
        $this->MODULE_VERSION_DATE = '2025-07-13';
        $this->MODULE_NAME = Loc::getMessage('SERGEYPR_CUSTOMTAB_MODULE_NAME') ?: 'Свои заметки CRM';
        $this->MODULE_DESCRIPTION = Loc::getMessage('SERGEYPR_CUSTOMTAB_MODULE_DESC') ?: 'Добавляет вкладку "Свои заметки" в карточки CRM';
        $this->PARTNER_NAME = Loc::getMessage('SERGEYPR_CUSTOMTAB_PARTNER_NAME') ?: 'Sergey Pr';
        $this->PARTNER_URI = 'https://github.com/dearkox';
    }

    /**
     * Установка модуля
     *
     * @return bool
     */
    public function DoInstall()
    {
        global $APPLICATION;

        // Проверяем наличие модуля CRM (обязательное условие)
        if (!Loader::includeModule('crm')) {
            $APPLICATION->ThrowException(
                Loc::getMessage('SERGEYPR_CUSTOMTAB_ERROR_CRM_NOT_INSTALLED') ?: 'Модуль CRM не установлен'
            );
            return false;
        }

        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();

        RegisterModule($this->MODULE_ID);

        return true;
    }

    /**
     * Удаление модуля
     *
     * @return bool
     */
    public function DoUninstall()
    {
        $this->UnInstallDB();
        $this->UnInstallEvents();
        $this->UnInstallFiles();

        UnRegisterModule($this->MODULE_ID);

        return true;
    }

    /**
     * Создание таблицы и добавление демоданных
     *
     * @return bool
     */
    public function InstallDB()
    {
        Loader::includeModule($this->MODULE_ID);

        $connection = Application::getConnection();
        $tableName = CrmNotesTable::getTableName();

        // Проверяем существование таблицы перед созданием
        if (!$connection->isTableExists($tableName)) {
            // Создаём таблицу через ORM-класс
            CrmNotesTable::getEntity()->createDbTable();
            $this->insertDemoData();
        }

        return true;
    }

    /**
     * Удаление таблицы
     *
     * @return bool
     */
    public function UnInstallDB()
    {
        Loader::includeModule($this->MODULE_ID);

        $connection = Application::getConnection();
        $tableName = CrmNotesTable::getTableName();

        // Проверяем существование таблицы перед удалением
        if ($connection->isTableExists($tableName)) {
            $connection->dropTable($tableName);
        }

        return true;
    }

    /**
     * Регистрация события onEntityDetailsTabsInitialized
     *
     * @return bool
     */
    public function InstallEvents()
    {
        // Регистрируем событие через EventManager
        \Bitrix\Main\EventManager::getInstance()->registerEventHandler(
            'crm',
            'OnEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            'SergeyPr\\CustomTab\\Handlers\\CrmTabHandler',
            'onEntityDetailsTabsInitialized'
        );
        return true;
    }

    /**
     * Удаление регистрации события
     *
     * @return bool
     */
    public function UnInstallEvents()
    {
        \Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler(
            'crm',
            'OnEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            'SergeyPr\\CustomTab\\Handlers\\CrmTabHandler',
            'onEntityDetailsTabsInitialized'
        );
        return true;
    }

    /**
     * Копирование компонента в /local/components/sergeypr/
     *
     * @return bool
     */
    public function InstallFiles()
    {
        CopyDirFiles(
            __DIR__ . '/components/crm.notes.grid',
            $_SERVER['DOCUMENT_ROOT'] . '/local/components/sergeypr/crm.notes.grid',
            true,  // перезаписывать существующие файлы
            true   // копировать рекурсивно со всеми подпапками
        );
        return true;
    }

    /**
     * Удаление компонента
     *
     * @return bool
     */
    public function UnInstallFiles()
    {
        DeleteDirFilesEx('/local/components/sergeypr/crm.notes.grid');
        return true;
    }

    /**
     * Добавление демонстрационных записей в таблицу crm_notes
     *
     * @return void
     */
    protected function insertDemoData()
    {
        // Данные для заполнения: [ENTITY_TYPE, ENTITY_ID, NOTE_TEXT]
        $demoData = [
            ['DEAL', 1, 'Срочно связаться с клиентом по сделке'],
            ['DEAL', 1, 'Проверить документы перед подписанием'],
            ['LEAD', 2, 'Первая встреча назначена на пятницу'],
            ['LEAD', 2, 'Отправить коммерческое предложение'],
            ['CONTACT', 3, 'Позвонить в четверг для уточнения деталей'],
            ['CONTACT', 3, 'Записать на вебинар по продукту'],
            ['COMPANY', 4, 'Обновить контактные данные компании'],
            ['COMPANY', 4, 'Согласовать договор с юристом'],
        ];

        // Добавляем записи через ORM
        foreach ($demoData as [$entityType, $entityId, $noteText]) {
            CrmNotesTable::add([
                'ENTITY_TYPE' => $entityType,
                'ENTITY_ID' => $entityId,
                'NOTE_TEXT' => $noteText,
                'CREATED_BY' => 1,
            ]);
        }
    }
}