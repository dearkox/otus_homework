<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

use SergeyPr\Medical\DoctorManager;
use SergeyPr\Medical\DoctorView;
use SergeyPr\Medical\ProcedureManager;

$APPLICATION->SetTitle("Врачи");
$APPLICATION->SetAdditionalCSS('/doctors/style.css');

// Простая маршрутизация
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Убираем базовый путь /doctors
$path = str_replace('/doctors', '', $path);
$path = trim($path, '/');

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($path === 'new') {
        // Добавление врача
        $code = $_POST['code'] ?? '';
        $lastName = $_POST['last_name'] ?? '';
        $firstName = $_POST['first_name'] ?? '';
        $middleName = $_POST['middle_name'] ?? '';
        $procedureIds = $_POST['procedures'] ?? [];

        if ($code && $lastName && $firstName) {
            DoctorManager::add($code, $lastName, $firstName, $middleName, $procedureIds);
        }
        LocalRedirect('/doctors/');
    } elseif ($path === 'newproc') {
        // Добавление процедуры
        $name = $_POST['name'] ?? '';
        if ($name) {
            ProcedureManager::add($name);
        }
        LocalRedirect('/doctors/');
    } elseif (strpos($path, 'edit/') === 0) {
        // Редактирование врача
        $code = str_replace('edit/', '', $path);
        $doctor = DoctorView::getByCode($code);
        if ($doctor) {
            $lastName = $_POST['last_name'] ?? '';
            $firstName = $_POST['first_name'] ?? '';
            $middleName = $_POST['middle_name'] ?? '';
            $procedureIds = $_POST['procedures'] ?? [];

            if ($lastName && $firstName) {
                DoctorManager::update($doctor['ID'], $code, $lastName, $firstName, $middleName, $procedureIds);
                LocalRedirect('/doctors/' . $code);
            }
        }
    }
}

// GET-маршруты
if ($path === '') {
    // Список врачей
    ?>
    <div style="text-align: center;">
        <div style="margin: 20px 0; text-align: left;">
            <a href="/doctors/new" class="btn">➕ Добавить врача</a>
            <a href="/doctors/newproc" class="btn">📋 Добавить процедуру</a>
        </div>
        <h1>Врачи</h1>
        <?= DoctorView::renderList() ?>
    </div>
    <?php
} elseif ($path === 'new') {
    // Форма добавления врача
    $procedures = ProcedureManager::getAll();
    ?>
    <div style="text-align: center;">
        <div style="margin: 20px 0; text-align: left;">
            <a href="/doctors/" class="btn">← Вернуться к списку врачей</a>
        </div>
        <h1>Врачи</h1>
        <h2>Данные врача</h2>
        <form method="post" style="max-width: 400px; margin: 0 auto;">
            <div style="margin-bottom: 15px;">
                <input type="text" name="code" placeholder="Название страницы врача (фамилия латиницей)" required style="width: 100%; padding: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <input type="text" name="last_name" placeholder="Фамилия врача" required style="width: 100%; padding: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <input type="text" name="first_name" placeholder="Имя врача" required style="width: 100%; padding: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <input type="text" name="middle_name" placeholder="Отчество врача" style="width: 100%; padding: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <select name="procedures[]" multiple size="5" style="width: 100%; padding: 8px;">
                    <option value="" disabled>Процедуры</option>
                    <?php foreach ($procedures as $proc): ?>
                        <option value="<?= $proc['ID'] ?>"><?= htmlspecialchars($proc['NAME']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn">Сохранить</button>
        </form>
    </div>
    <?php
} elseif ($path === 'newproc') {
    // Форма добавления процедуры
    ?>
    <div style="text-align: center;">
        <div style="margin: 20px 0; text-align: left;">
            <a href="/doctors/" class="btn">← Вернуться к списку врачей</a>
        </div>
        <h1>Врачи</h1>
        <h2>Добавить процедуру</h2>
        <form method="post" style="max-width: 400px; margin: 0 auto;">
            <div style="margin-bottom: 15px;">
                <input type="text" name="name" placeholder="Название процедуры" required style="width: 100%; padding: 8px;">
            </div>
            <button type="submit" class="btn">Сохранить</button>
        </form>
    </div>
    <?php
} elseif (strpos($path, 'edit/') === 0) {
    // Форма редактирования врача
    $code = str_replace('edit/', '', $path);
    $doctor = DoctorView::getByCode($code);
    $procedures = ProcedureManager::getAll();
    $doctorProcedures = DoctorView::getProceduresByDoctorCode($code);
    $doctorProcedureIds = array_column($doctorProcedures, 'ID');

    if (!$doctor) {
        LocalRedirect('/doctors/');
    }
    ?>
    <div style="text-align: center;">
        <div style="margin: 20px 0; text-align: left;">
            <a href="/doctors/" class="btn">← Вернуться к списку врачей</a>
        </div>
        <h1>Врачи</h1>
        <h2>Редактирование данных врача</h2>
        <form method="post" style="max-width: 400px; margin: 0 auto;">
            <div style="margin-bottom: 15px;">
                <input type="text" name="code" value="<?= htmlspecialchars($doctor['CODE']) ?>" placeholder="Название страницы врача (фамилия латиницей)" required style="width: 100%; padding: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <input type="text" name="last_name" value="<?= htmlspecialchars($doctor['LAST_NAME_VALUE']) ?>" placeholder="Фамилия врача" required style="width: 100%; padding: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <input type="text" name="first_name" value="<?= htmlspecialchars($doctor['FIRST_NAME_VALUE']) ?>" placeholder="Имя врача" required style="width: 100%; padding: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <input type="text" name="middle_name" value="<?= htmlspecialchars($doctor['MIDDLE_NAME_VALUE']) ?>" placeholder="Отчество врача" style="width: 100%; padding: 8px;">
            </div>
            <div style="margin-bottom: 15px;">
                <select name="procedures[]" multiple size="5" style="width: 100%; padding: 8px;">
                    <option value="" disabled>Процедуры</option>
                    <?php foreach ($procedures as $proc): ?>
                        <option value="<?= $proc['ID'] ?>" <?= in_array($proc['ID'], $doctorProcedureIds) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($proc['NAME']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn">Сохранить</button>
        </form>
    </div>
    <?php
} else {
    // Детальная страница врача
    $code = $path;
    $doctor = DoctorView::getByCode($code);
    if (!$doctor) {
        LocalRedirect('/doctors/');
    }
    ?>
    <div style="text-align: center;">
        <div style="margin: 20px 0; text-align: left;">
            <a href="/doctors/" class="btn">← Вернуться к списку врачей</a>
            <a href="/doctors/edit/<?= htmlspecialchars($code) ?>" class="btn" style="margin-left: 10px;">✏️ Изменить данные врача</a>
        </div>
        <h1>Врачи</h1>
        <?= DoctorView::renderDetail($code) ?>
    </div>
    <?php
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");