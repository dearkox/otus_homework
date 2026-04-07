<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

use SergeyPr\Debugger\Debug;

$APPLICATION->SetTitle("Отладка и логирование");

echo '<h2>0. Закрепление знаний по уроку и практическое их применение</h2>';

echo '<pre>';
echo 'Обновил на сервере composer и установил пакет var-dumper: <br />';


dump("var-dumper version: " . Composer\InstalledVersions::getVersion('symfony/var-dumper'));

echo "------------------------\n";
echo '</pre>';
$packages = \Composer\InstalledVersions::getInstalledPackages();
echo '<pre>';
echo 'Установленные пакеты: <br />';

foreach ($packages as $packageName) {
    echo "Пакет: {$packageName}\n";
    echo "Версия: " . \Composer\InstalledVersions::getPrettyVersion($packageName) . "\n";
    echo "------------------------\n";
}
echo '</pre>';
echo '<h2>1. Создать файлы для ДЗ согласно репозиторию https://github.com/OtusTeam/bitrix24.</h2>';
echo '<pre>';
echo 'В /local/lib -решил что буду хранить свои библиотеки классов для будующих уроков, соответственно с учётом правил PSR-4<br />';
echo 'Написал свой autoload.php и разместил его в в папке /local/lib/ где согласно правилам именование производтся по схеме Vendor\Namespace\Class <br />';
echo 'Для простоты понимания преподавателям папка /local/lib/ - является Vendor в моём проекте. Так что текущий мой класс /local/lib/Debugger/Debug.php вызывается  <br />';
echo 'Как SergeyPr\Debugger\Debug<br />';
echo 'Далее просто выполняю один из своих методов класса простой var_dump с переменной $packages<br />';
Debug::dump($packages);
echo "------------------------\n";
echo '</pre>';

echo '<h2>2.В нем написать код, который, при обращении к нему по HTTP, будет записывать в файл текущие дату и время.</h2>';
echo '<pre>';
echo 'Далее я написал функционал:  <br />';


highlight_string('<?php
#Проверяем что запрос выполнен через HTTP а не через командную строку,
# а также что бы функционал сработал и через GET метод и POST что бы преподаватель мог выполнить его просто зайдя на страницу
if (isset($_SERVER[\'REQUEST_METHOD\']) && php_sapi_name() !== \'cli\') {
    file_put_contents(
        $_SERVER[\'DOCUMENT_ROOT\'] . \'/local/logs/access.log\',
        date(\'Y-m-d H:i:s\') . \' - \' . $_SERVER[\'REQUEST_METHOD\'] . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}
?>');

if (isset($_SERVER['REQUEST_METHOD']) && php_sapi_name() !== 'cli') {
    file_put_contents(
        $_SERVER['DOCUMENT_ROOT'] . '/local/logs/access.log',
        date('Y-m-d H:i:s') . ' - ' . $_SERVER['REQUEST_METHOD'] . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}
echo 'Тут он уже выполнился и мы выведем файл лога /local/logs/access.log:  <br />';
$accessContent = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/local/logs/access.log');
dump($accessContent);
echo "------------------------\n";
echo '</pre>';


echo '<h2>3. Написать и подключить собственный класс системного логгера, который будет переопределять форматирование строк лога - добавлять слово OTUS в каждую строку.</h2>';

echo '<pre>';
echo 'Сам .settings.php я править не стал(спасибо огромное за урок) использовал /local/.settings_extra.php<br />';
echo 'Со своими настройками, далее его содержимое:<br />';

highlight_string('<?php
return [
    \'exception_handling\' => [
        \'value\' => [
            \'debug\' => true,
            \'handled_errors_types\' => E_ALL && ~E_WARNING && ~E_NOTICE && ~E_STRICT && ~E_USER_NOTICE && ~E_DEPRECATED,
            \'exception_errors_types\' => E_ALL && ~E_NOTICE && ~E_STRICT && ~E_USER_NOTICE && ~E_DEPRECATED,
            \'ignore_silence\' => false,
            \'assertion_throws_exception\' => true,
            \'assertion_error_type\' => 256,
            \'log\' => [
                \'class_name\' => \\SergeyPr\\Debugger\\Debug::class,
                \'required_file\' => $_SERVER[\'DOCUMENT_ROOT\'] . \'/local/lib/Debugger/Debug.php\',
                \'settings\' => [
                    \'file\' => \'local/logs/exceptions.log\',
                    \'log_size\' => 1000000,
                    \'level\' => 3,
                ],
            ],
        ],
        \'readonly\' => false,
    ],
];
?>');

echo "------------------------\n";
echo '</pre>';
echo '<pre>';

echo '<pre>';
echo 'Так выглядит сам класс /local/lib/Debugger/Debug.php <br />';

highlight_string('<?php

namespace SergeyPr\Debugger;

use Bitrix\Main\Diag\ExceptionHandlerFormatter;
use Bitrix\Main\Diag\FileExceptionHandlerLog;

class Debug extends FileExceptionHandlerLog
{

    private int $logLevel;

    /**
     * Сохраняем уровень логирования из настроек для использования в write()
     * Родительский initialize() при этом отрабатывает стандартно
     *
     * @param array $options
     * @return void
     */
    public function initialize(array $options)
    {
        parent::initialize($options);
        $this->logLevel = $options[\'level\'] ?? 0;
    }

    /**
     * Переопределяет функцию для изменениея строки вывода лога
     *
     * @param $exception
     * @param $logType
     * @return void
     */
    public function write($exception, $logType): void
    {
        $text = ExceptionHandlerFormatter::format($exception, false, $this->logLevel);

        $context = [
            \'type\' => static::logTypeToString($logType),
        ];

        $logLevel = static::logTypeToLevel($logType);

        // Добавляем в вывод OTUS Logger
        $message = "{date} OTUS Logger - Host: {host} - {type} - {$text}\n";

        $this->logger->log($logLevel, $message, $context);
    }

    /**
     * Выводит сообщение с дампом переменной (обёртка над var_dump)
     *
     * @param mixed $data
     * @return void
     */
    public static function dump(mixed $data): void
    {
        echo \'<pre>\';
        var_dump($data);
        echo \'</pre>\';
    }
}
?>');
echo "------------------------\n";
echo '</pre>';

echo '<pre>';
echo 'Сначало выведу содержимое лога для исключений при ошибке, так как обработчик прерывает при создании ошибки дальнейший вывод<br />';
echo 'Содержимое лога (/local/logs/exceptions.log):<br>';
$exceptionsContent = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/local/logs/exceptions.log');
dump($exceptionsContent);
echo '</pre>';

echo 'Теперь вызовем несуществующую функцию unExistFunction($packages) что бы вызвать обработчик ошибок своего класса настроенного в /local/php_interface/.settings_extra.php: <br />';
echo 'Настройки выведут ошибку в этот документ(остановив его дальнейшую обработику) и запишут в лог указанный в настройках /local/logs/exceptions.log<br />';
unExistFunction($packages);
echo "------------------------\n";
echo '</pre>';


?>
<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
