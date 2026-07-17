// /local/js/ProcedureRecords/script.js

(function() {
    'use strict';

    /** @type {boolean} Флаг для предотвращения повторной инициализации календаря */
    var popupInitialized = false;

    /**
     * Обработчик события BX.ready
     * Находит все ссылки на процедуры и навешивает на них обработчики клика
     */
    BX.ready(function() {
        /** @type {NodeList} Все ссылки с классом procedure-record-link */
        var links = document.querySelectorAll('.procedure-record-link');

        links.forEach(function(link) {
            BX.bind(link, 'click', function(e) {
                e.preventDefault();

                /** @type {string} ID процедуры из data-атрибута */
                var procedureId = this.dataset.procedureId;
                /** @type {string} ID врача из data-атрибута */
                var doctorId = this.dataset.doctorId;
                /** @type {string} Название процедуры из data-атрибута */
                var procedureName = this.dataset.procedureName;

                openBookingPopup(procedureId, doctorId, procedureName);
            });
        });
    });

    /**
     * Открывает модальное окно бронирования
     *
     * @param {string} procedureId - ID процедуры
     * @param {string} doctorId - ID врача
     * @param {string} procedureName - Название процедуры
     */
    function openBookingPopup(procedureId, doctorId, procedureName) {
        var content = createPopupContent(procedureId, doctorId, procedureName);

        var popup = BX.PopupWindowManager.create(
            'booking-popup',
            null,
            {
                titleBar: 'Запись на процедуру: ' + procedureName,
                content: content,
                width: 450,
                height: 300,
                closeIcon: true,
                buttons: [
                    new BX.PopupWindowButton({
                        text: 'Забронировать',
                        className: 'popup-window-button-accept',
                        events: {
                            click: function() {
                                submitBooking(procedureId, doctorId, popup);
                            }
                        }
                    }),
                    new BX.PopupWindowButton({
                        text: 'Отмена',
                        events: {
                            click: function() {
                                popup.close();
                            }
                        }
                    })
                ],
                events: {
                    onPopupShow: function() {
                        if (!popupInitialized) {
                            /** @type {HTMLInputElement} Поле ввода даты и времени */
                            var datetimeInput = document.getElementById('booking-datetime');
                            if (datetimeInput) {
                                BX.bind(datetimeInput, 'click', function() {
                                    BX.calendar({
                                        node: datetimeInput,
                                        field: datetimeInput,
                                        bTime: true,
                                        bHideTime: false,
                                    });
                                });
                                popupInitialized = true;
                            }
                        }
                    }
                }
            }
        );

        popup.show();
    }

    /**
     * Создаёт HTML-контент для модального окна
     *
     * @param {string} procedureId - ID процедуры
     * @param {string} doctorId - ID врача
     * @param {string} procedureName - Название процедуры
     * @returns {string} HTML-код содержимого
     */
    function createPopupContent(procedureId, doctorId, procedureName) {
        return [
            '<div style="padding: 15px;">',
            '<div style="margin-bottom: 15px;">',
            '<label>ФИО пациента:</label><br>',
            '<input type="text" id="booking-patient-name" style="width: 100%; padding: 5px; box-sizing: border-box;" placeholder="Введите ФИО">',
            '</div>',
            '<div style="margin-bottom: 15px;">',
            '<label>Дата и время:</label><br>',
            '<input type="text" id="booking-datetime" style="width: 100%; padding: 5px; box-sizing: border-box;" placeholder="Нажмите, чтобы выбрать дату и время">',
            '</div>',
            '<div style="font-size: 12px; color: #666; margin-top: 5px;">',
            '<span>Процедура: <strong>' + procedureName + '</strong></span>',
            '</div>',
            '<input type="hidden" id="booking-procedure-id" value="' + procedureId + '">',
            '<input type="hidden" id="booking-doctor-id" value="' + doctorId + '">',
            '</div>'
        ].join('');
    }

    /**
     * Отправляет AJAX-запрос на создание бронирования
     *
     * @param {string} procedureId - ID процедуры
     * @param {string} doctorId - ID врача
     * @param {BX.PopupWindow} popup - Ссылка на модальное окно
     */
    function submitBooking(procedureId, doctorId, popup) {
        /** @type {HTMLInputElement} Поле ввода ФИО */
        var patientName = document.getElementById('booking-patient-name');
        /** @type {HTMLInputElement} Поле ввода даты и времени */
        var datetime = document.getElementById('booking-datetime');

        if (!patientName.value.trim()) {
            alert('Введите ФИО пациента');
            patientName.focus();
            return;
        }

        if (!datetime.value.trim()) {
            alert('Выберите дату и время');
            datetime.focus();
            return;
        }

        /**
         * Преобразование даты из формата DD.MM.YYYY HH:MM:SS → YYYY-MM-DD HH:MM:SS
         * Необходимо для сохранения в БД
         */
        var dateStr = datetime.value.trim();
        var parts = dateStr.split(' ');
        var dateParts = parts[0].split('.');
        var timeParts = parts[1] ? parts[1].split(':') : ['00', '00', '00'];

        var formattedDate = dateParts[2] + '-' +
            dateParts[1].padStart(2, '0') + '-' +
            dateParts[0].padStart(2, '0') + ' ' +
            timeParts[0].padStart(2, '0') + ':' +
            (timeParts[1] || '00').padStart(2, '0') + ':' +
            (timeParts[2] || '00').padStart(2, '0');

        /** @type {BX.PopupWindowButton} Кнопка "Забронировать" */
        var submitButton = popup.getButton('save');
        if (submitButton) {
            submitButton.setText('Сохранение...');
            submitButton.setDisabled(true);
        }

        /**
         * AJAX-запрос к обработчику /local/ajax/booking.php
         */
        BX.ajax({
            url: '/local/ajax/booking.php',
            method: 'POST',
            data: {
                patientName: patientName.value.trim(),
                procedureId: procedureId,
                doctorId: doctorId,
                datetime: formattedDate
            },
            dataType: 'json',
            onsuccess: function(response) {
                if (response.status === 'success') {
                    alert('Бронирование создано!');
                    popup.close();
                    window.location.href = '/services/lists/20/view/';
                } else {
                    alert('Ошибка: ' + (response.message || 'Не удалось создать бронирование'));
                }

                if (submitButton) {
                    submitButton.setText('Забронировать');
                    submitButton.setDisabled(false);
                }
            },
            onfailure: function(error) {
                console.error(error);
                alert('Произошла ошибка при создании бронирования');

                if (submitButton) {
                    submitButton.setText('Забронировать');
                    submitButton.setDisabled(false);
                }
            }
        });
    }
})();