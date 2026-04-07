<?php
# Автозагрузчик composer
if(file_exists($_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
}

# Автозагрузчик для папки /local/lib в SergeyPr пространство имён
if(file_exists($_SERVER['DOCUMENT_ROOT'] . '/local/lib/autoload.php')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/local/lib/autoload.php';
}