<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

use SergeyPr\Medical\ORM\AppointmentsTable;
use Bitrix\Main\Entity\ReferenceField;
use Bitrix\Iblock\Elements\ElementDoctorsTable;
use Bitrix\Iblock\Elements\ElementProceduresTable;
use Bitrix\Main\Loader;

Loader::includeModule('iblock');

$APPLICATION->SetTitle("Записи на приём");


$appointments = AppointmentsTable::getList([
        'select' => [
                'ID',
                'APPOINTMENT_DATE',
                'FIRST_NAME',
                'LAST_NAME',
                'PHONE',
                'DOCTOR_ID',
                'PROCEDURE_ID',
                'DOCTOR_NAME' => 'DOCTOR.NAME',
                'PROCEDURE_NAME' => 'PROCEDURE.NAME'
        ],
        'runtime' => [
                new ReferenceField(
                        'DOCTOR',
                        ElementDoctorsTable::class,
                        ['=this.DOCTOR_ID' => 'ref.ID']
                ),
                new ReferenceField(
                        'PROCEDURE',
                        ElementProceduresTable::class,
                        ['=this.PROCEDURE_ID' => 'ref.ID']
                ),
        ],
        'order' => ['APPOINTMENT_DATE' => 'ASC'], // Упорядочиваем по записи пациента
])->fetchAll();
?>

    <div class="appointments-list">
        <h1>Записи на приём</h1>

        <?php if (empty($appointments)): ?>
            <p>Записей не найдено.</p>
        <?php else: ?>
            <?php foreach ($appointments as $item): ?>
                <div class="appointment-item">
                    <div class="date"><?= htmlspecialchars($item['APPOINTMENT_DATE']) ?></div>
                    <div class="patient">Пациент: <?= htmlspecialchars($item['LAST_NAME'] . ' ' . $item['FIRST_NAME']) ?></div>
                    <div class="phone">Телефон: <?= htmlspecialchars($item['PHONE']) ?></div>
                    <div class="doctor">Врач: <?= htmlspecialchars($item['DOCTOR_NAME']) ?></div>
                    <div class="procedure">Процедура: <?= htmlspecialchars($item['PROCEDURE_NAME']) ?></div>
                </div>
                <hr class="appointment-divider">
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

<?php require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>