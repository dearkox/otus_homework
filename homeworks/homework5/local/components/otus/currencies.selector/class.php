<?php

use Bitrix\Main\Loader;
use Bitrix\Currency\CurrencyManager;
use Bitrix\Currency\CurrencyRateTable;

class CurrenciesSelectorComponent extends CBitrixComponent
{
    /**
     * Обработка параметров компонента перед выполнением
     *
     * @param array $arParams Входные параметры
     * @return array
     */
    public function onPrepareComponentParams($arParams)
    {
        $arParams['CURRENCY'] = trim($arParams['CURRENCY'] ?? '');
        if ($arParams['CURRENCY'] === '') {
            $arParams['CURRENCY'] = CurrencyManager::getBaseCurrency();
        }
        return $arParams;
    }

    /**
     * Точка входа в компонент
     *
     * @return void
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function executeComponent()
    {
        if (!$this->checkModules()) {
            $this->includeComponentTemplate();
            return;
        }

        $this->arResult['CURRENCY'] = $this->arParams['CURRENCY'];
        $this->arResult['RATE'] = $this->getCurrencyRate();

        $this->includeComponentTemplate();
    }

    /**
     * Проверка загрузки модуля валют
     *
     * @return bool
     * @throws \Bitrix\Main\LoaderException
     */
    protected function checkModules()
    {
        if (!Loader::includeModule('currency')) {
            $this->arResult['ERROR'] = 'Модуль валют не установлен';
            return false;
        }
        return true;
    }

    /**
     * Получение курса валюты
     *
     * @return float|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    protected function getCurrencyRate()
    {
        $currency = $this->arParams['CURRENCY'];
        $baseCurrency = CurrencyManager::getBaseCurrency();

        // Если базовая валюта совпадает с выбранной, курс = 1
        if ($currency === $baseCurrency) {
            return 1.0;
        }

        // Получаем текущую дату для курса
        $today = new \Bitrix\Main\Type\Date();

        // Ищем курс на сегодня
        $rateRecord = CurrencyRateTable::getList([
            'select' => ['RATE'],
            'filter' => [
                '=CURRENCY' => $currency,
                '=BASE_CURRENCY' => $baseCurrency,
                '<=DATE_RATE' => $today,
            ],
            'order' => ['DATE_RATE' => 'DESC'],
            'limit' => 1,
        ])->fetch();

        if ($rateRecord) {
            return (float)$rateRecord['RATE'];
        }

        // Если курса на сегодня нет, пробуем получить курс по умолчанию из таблицы валют
        $currencyData = \Bitrix\Currency\CurrencyTable::getList([
            'select' => ['AMOUNT'],
            'filter' => ['=CURRENCY' => $currency],
            'limit' => 1,
        ])->fetch();

        return $currencyData ? (float)$currencyData['AMOUNT'] : null;
    }
}