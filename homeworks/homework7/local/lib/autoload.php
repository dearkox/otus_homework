<?php


/**
 * PSR-4 автозагрузчик для пространства имён SergeyPr
 *
 * Регистрирует автозагрузчик, который преобразует пространства имён
 * в пути к файлам согласно стандарту PSR-4.
 *
 * @return void
 */
spl_autoload_register(function (string $class): void {
    // Префикс пространства имён (vendor)
    $prefix = 'SergeyPr\\';

    // Базовая директория для классов (относительно текущего файла)
    $base_dir = __DIR__ . '/';

    // Длина префикса
    $len = strlen($prefix);

    // Проверяем, начинается ли класс с нашего префикса
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    // Относительное имя класса (без префикса)
    $relative_class = substr($class, $len);

    // Преобразуем пространство имён в путь к файлу
    // SergeyPr\Captcha\Generator -> Captcha/Generator.php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Если файл существует — подключаем
    if (file_exists($file)) {
        require $file;
    }
});