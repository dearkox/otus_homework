<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
?>

<div class="currency-rate">
    <?php if (isset($arResult['ERROR'])): ?>
        <p style="color: red;"><?= htmlspecialchars($arResult['ERROR']) ?></p>
    <?php else: ?>
        <p>
            <strong>Валюта:</strong> <?= htmlspecialchars($arResult['CURRENCY']) ?><br>
            <strong>Курс к базовой валюте:</strong> <?= htmlspecialchars($arResult['RATE']) ?>
        </p>
    <?php endif; ?>
</div>