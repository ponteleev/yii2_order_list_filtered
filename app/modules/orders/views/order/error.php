<?php

declare(strict_types=1);

use yii\helpers\Html;
use yii\web\View;

/**
 * Шаблон отображения системных ошибок (404, 500) для модуля заказов.
 *
 * @var View $this
 * @var string $name Название ошибки (например, Not Found (#404))
 * @var string $message Текст системного сообщения
 * @var \Exception $exception Сам объект перехваченного исключения
 */

$this->title = $name;
?>
<div class="container" style="padding-top: 50px;">
    <div class="alert alert-danger" style="padding: 30px; border-radius: 6px;">
        <h1 style="margin-top: 0; font-weight: bold;">
            <span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
            <?= Html::encode($name) ?>
        </h1>
        <p style="font-size: 18px; margin-top: 15px;">
            <?= nl2br(Html::encode($message)) ?>
        </p>
    </div>

    <div style="margin-top: 20px;">
        <p style="color: #666;">
            <!-- Используем наши новые кодовые ключи i18n локализации -->
            <?= Yii::t('modules/orders', 'orders.error.not_found') ?>
        </p>
        <a href="<?= \yii\helpers\Url::to(['/orders/order/index']) ?>" class="btn btn-primary" style="margin-top: 10px;">
            <span class="glyphicon glyphicon-arrow-left" aria-hidden="true"></span>
            <?= Yii::t('modules/orders', 'orders.tabs.all') ?>
        </a>
    </div>
</div>
