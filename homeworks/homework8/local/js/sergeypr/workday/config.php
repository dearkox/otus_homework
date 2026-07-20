<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

return [
    'js' => 'script.js',
    'lang' => 'lang/' . LANGUAGE_ID . '/script.php', // Автоподключение языкового файла
    'rel' => [
        'main.core',
        'ui.dialogs.messagebox',
        'ui.buttons',
    ],
];