<?php

declare(strict_types=1);

use yii\helpers\Html;
use app\models\Order;

/**
 * @var Order $order
 */

$statusesMap = [
    Order::STATUS_PENDING     => Yii::t('modules/orders', 'orders.status.pending'),
    Order::STATUS_IN_PROGRESS => Yii::t('modules/orders', 'orders.status.in_progress'),
    Order::STATUS_COMPLETED   => Yii::t('modules/orders', 'orders.status.completed'),
    Order::STATUS_CANCELED    => Yii::t('modules/orders', 'orders.status.canceled'),
    Order::STATUS_ERROR       => Yii::t('modules/orders', 'orders.status.error'),
];
?>
<tr>
    <td><?= $order->id ?></td>
    <td>
        <?= Html::encode($order->user ? $order->user->first_name . ' ' . $order->user->last_name : Yii::t('modules/orders', 'orders.user.guest')) ?>
    </td>
    <td class="link"><?= Html::encode($order->link) ?></td>
    <td><?= $order->quantity ?></td>
    <td class="service">
        <span class="label-id"><?= $order->service_id ?></span>
        <?= Html::encode($order->service ? $order->service->name : '') ?>
    </td>
    <td><?= Html::encode($statusesMap[(int)$order->status] ?? 'Unknown') ?></td>
    <td>
        <?= (int)$order->mode === Order::MODE_AUTO ? Yii::t('modules/orders', 'orders.mode.auto') : Yii::t('modules/orders', 'orders.mode.manual') ?>
    </td>
    <td>
        <span class="nowrap"><?= Yii::$app->formatter->asDate((int)$order->created_at, 'yyyy-MM-dd') ?></span>
        <span class="nowrap"><?= Yii::$app->formatter->asTime((int)$order->created_at, 'HH:mm:ss') ?></span>
    </td>
</tr>
