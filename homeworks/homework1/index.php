<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Отчёт по настройке Git");
?>

<h1>Отчёт по настройке Git и работе с домашками</h1>
<ol>
    <li>Настроил VSCode: установил расширение SFTP от Natizyskunk, сконфигурировал подключение к Timeweb (FTP, хост vh434.timeweb.ru, remotePath /public_html). Git использую через терминал.</li>
    <li>Установил Битрикс24 корпоративный портал на Timeweb, настроил Push&amp;Pull и обновил до актуальной версии сам Битрикс.</li>
    <li>Установил Git на Windows 11 (default fast-forward/merge, fs cache).</li>
    <li>Настроил <code>user.name</code> и <code>user.email</code>.</li>
    <li>Сгенерировал SSH-ключ ed25519, добавил публичный ключ в GitHub.</li>
    <li>В папке <code>H:\www\OTUS\cc675955.tw1.ru</code> выполнил <code>git init</code>.</li>
    <li>Привязал удалённый репозиторий: <code>git remote add origin git@github.com:dearkox/otus_homework.git</code>.</li>
    <li>Создал <code>.gitignore</code> с правилом игнорирования всего, кроме папки <code>homeworks/</code> и самого <code>.gitignore</code>.</li>
    <li>Создал папку <code>homeworks/homework1/</code> и файл <code>readme.md</code>.</li>
    <li>Выполнил <code>git add .</code></li>
    <li>Переименовал ветку в <code>main</code>: <code>git branch -M main</code></li>
    <li>Сделал коммит: <code>git commit -m "feat: инициализация структуры homeworks/"</code></li>
    <li>Отправил на GitHub: <code>git push -u origin main</code></li>
    <li>На timeweb в папке <code>public_html/</code> выполнил <code>git init</code></li>
    <li>Привязал через HTTPS удалённый репозиторий <code>git remote add origin https://github.com/dearkox/otus_homework.git</code></li>
    <li>Создал в локальной папке <code>homeworks/homework1/index.php</code> добавил описание с домашней работой</li>
    <li>Сделал коммит и запушил</li>
    <li>На timeweb сделал <code>git pull origin main</code></li>
</ol>


<?php require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"); ?>