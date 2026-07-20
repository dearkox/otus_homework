// /local/js/sergeypr/workday/script.js

(function() {
    'use strict';

    /**
     * Инициализирует обработчики на кнопки "Начать" и "Продолжить".
     * Вызывается при каждой загрузке страницы и при изменении DOM.
     *
     * Проблема: кнопки управления рабочим днём пересоздаются в DOM после каждого действия
     * (остановка/запуск). Обработчики, повешенные на старые элементы, теряются.
     * Решение: используем MutationObserver для отслеживания изменений в DOM
     * и повторно навешиваем обработчики на новые кнопки.
     */
    function initWorkdayInterceptor() {
        // 1. Кнопка "Начать" — имеет постоянный ID
        var startBtn = document.getElementById('buttonStartDropdownAnchorText');
        if (startBtn && startBtn.dataset.workdayHandler !== 'Y') {
            // Вешаем обработчик в фазе capture (true), чтобы перехватить событие до других обработчиков
            startBtn.addEventListener('click', handleStartClick, true);
            // Отмечаем кнопку, чтобы не вешать обработчик повторно на тот же элемент
            startBtn.dataset.workdayHandler = 'Y';
        }

        // 2. Кнопка "Продолжить" — не имеет ID, ищем по классам и иконке
        var continueIcon = document.querySelector('.tm-control-panel__action .ui-icon-set.--play-l');
        if (continueIcon) {
            // Поднимаемся от иконки к самой кнопке
            var continueBtn = continueIcon.closest('.tm-control-panel__action');
            if (continueBtn && continueBtn.dataset.workdayHandler !== 'Y') {
                continueBtn.addEventListener('click', handleContinueClick, true);
                continueBtn.dataset.workdayHandler = 'Y';
            }
        }
    }

    /**
     * Обрабатывает клик по кнопке "Начать рабочий день".
     *
     * Проблема: стандартный обработчик Битрикс перехватывает клик и запускает рабочий день
     * без подтверждения. Нужно прервать его и показать модалку.
     * Решение: preventDefault + stopPropagation + stopImmediatePropagation
     * отключают стандартное поведение. После подтверждения эмулируем клик по кнопке,
     * чтобы запустить стандартный механизм Битрикс.
     *
     * @param {Event} event
     */
    function handleStartClick(event) {
        var startBtn = event.currentTarget;

        // Защита от повторного открытия модалки во время выполнения
        if (startBtn.dataset.myConfirmed === 'Y') {
            return;
        }

        // Отключаем все другие обработчики этого события
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();

        BX.Runtime.loadExtension('ui.dialogs.messagebox').then(function() {
            var messageBox = BX.UI.Dialogs.MessageBox.create({
                title: BX.message('SERGEYPR_WORKDAY_TITLE'),
                message: BX.message('SERGEYPR_WORKDAY_MESSAGE'),
                modal: true,
                buttons: [
                    new BX.UI.Button({
                        text: BX.message('SERGEYPR_WORKDAY_BTN_START'),
                        color: BX.UI.Button.Color.SUCCESS,
                        onclick: function() {
                            // Устанавливаем флаг, чтобы не открывать модалку повторно
                            startBtn.dataset.myConfirmed = 'Y';
                            messageBox.close();

                            // Эмулируем клик по кнопке — теперь стандартный обработчик сработает
                            startBtn.click();

                            // Сбрасываем флаг через секунду, чтобы не блокировать будущие клики
                            setTimeout(function() {
                                delete startBtn.dataset.myConfirmed;
                            }, 1000);
                        }
                    }),
                    new BX.UI.Button({
                        text: BX.message('SERGEYPR_WORKDAY_BTN_CANCEL'),
                        color: BX.UI.Button.Color.LINK,
                        onclick: function() {
                            messageBox.close();
                        }
                    })
                ]
            });

            messageBox.show();
        }).catch(function(error) {
            console.error(error);
        });
    }

    /**
     * Обрабатывает клик по кнопке "Продолжить рабочий день".
     * Логика полностью аналогична handleStartClick, но используются другие языковые фразы.
     *
     * @param {Event} event
     */
    function handleContinueClick(event) {
        var continueBtn = event.currentTarget;

        if (continueBtn.dataset.myConfirmed === 'Y') {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();

        BX.Runtime.loadExtension('ui.dialogs.messagebox').then(function() {
            var messageBox = BX.UI.Dialogs.MessageBox.create({
                title: BX.message('SERGEYPR_WORKDAY_CONTINUE_TITLE'),
                message: BX.message('SERGEYPR_WORKDAY_CONTINUE_MESSAGE'),
                modal: true,
                buttons: [
                    new BX.UI.Button({
                        text: BX.message('SERGEYPR_WORKDAY_BTN_CONTINUE'),
                        color: BX.UI.Button.Color.SUCCESS,
                        onclick: function() {
                            continueBtn.dataset.myConfirmed = 'Y';
                            messageBox.close();
                            continueBtn.click();
                            setTimeout(function() {
                                delete continueBtn.dataset.myConfirmed;
                            }, 1000);
                        }
                    }),
                    new BX.UI.Button({
                        text: BX.message('SERGEYPR_WORKDAY_BTN_CANCEL'),
                        color: BX.UI.Button.Color.LINK,
                        onclick: function() {
                            messageBox.close();
                        }
                    })
                ]
            });

            messageBox.show();
        }).catch(function(error) {
            console.error(error);
        });
    }

    /**
     * Запускает наблюдение за изменениями DOM.
     *
     * Проблема: кнопки управления рабочим днём пересоздаются после каждого действия
     * (остановка/запуск). MutationObserver позволяет перехватывать эти изменения
     * и повторно навешивать обработчики на новые кнопки.
     */
    function startObserver() {
        // Ждём появления body, чтобы не пытаться наблюдать за null
        if (!document.body) {
            setTimeout(startObserver, 50);
            return;
        }

        // Первичная инициализация
        initWorkdayInterceptor();

        // Наблюдаем за изменениями в DOM
        var observer = new MutationObserver(function() {
            // При любом изменении DOM пробуем навесить обработчики заново
            initWorkdayInterceptor();
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Запускаем инициализацию после полной загрузки DOM
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        startObserver();
    } else {
        document.addEventListener('DOMContentLoaded', startObserver);
    }
})();