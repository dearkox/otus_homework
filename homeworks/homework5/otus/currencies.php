<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Выбор валюты через настройки компонента и вывод курса в шаблоне");
?>
<?php $APPLICATION->IncludeComponent(
	"otus:currencies.selector",
	"",
	Array(
		"CURRENCY" => "EUR"
	)
);?>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>