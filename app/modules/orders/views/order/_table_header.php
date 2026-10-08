<?php

declare(strict_types=1);

use ModuleOrders\models\OrderSearch;
use yii\helpers\Html;
use app\models\Order;

/**
 * @var OrderSearch $searchModel
 * @var array $dropdownServices
 * @var int $totalAllServicesCount
 * @var closure $filterUrl
 */
?>
<tr>
    <th><?= Yii::t('modules/orders', 'orders.column.id') ?></th>
    <th><?= Yii::t('modules/orders', 'orders.column.user') ?></th>
    <th><?= Yii::t('modules/orders', 'orders.column.link') ?></th>
    <th><?= Yii::t('modules/orders', 'orders.column.quantity') ?></th>

    <!-- Фильтр: Service -->
    <th class="dropdown-th">
        <div class="dropdown">
            <button class="btn btn-th btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                <?= Yii::t('modules/orders', 'orders.column.service') ?>
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                <li class="<?= $searchModel->service_id === null ? 'active' : '' ?>">
                    <!-- Исправлено замечание ревью: теперь All выводит общую сумму каунтеров, а не тотал датапровайдера -->
                    <a href="<?= $filterUrl('service_id', null) ?>"><?= Yii::t('modules/orders', 'orders.filter.all') ?> (<?= $totalAllServicesCount ?>)</a>
                </li>
                <?php foreach ($dropdownServices as $item): ?>
                    <?php if ($item['disabled']): ?>
                        <li class="grey disabled" style="padding: 3px 20px; color: #c1c1c1; cursor: not-allowed;">
                            <span class="label-id" style="border-color: #eee;"><?= $item['id'] ?></span>
                            <?= Html::encode($item['name']) ?> (0)
                        </li>
                    <?php else: ?>
                        <li class="<?= (int)$searchModel->service_id === $item['id'] ? 'active' : '' ?>">
                            <a href="<?= $filterUrl('service_id', (string)$item['id']) ?>">
                                <span class="label-id"><?= $item['id'] ?></span>
                                <?= Html::encode($item['name']) ?> (<?= $item['count'] ?>)
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>
    </th>

    <th><?= Yii::t('modules/orders', 'orders.column.status') ?></th>

    <!-- Фильтр: Mode -->
    <th class="dropdown-th">
        <div class="dropdown">
            <button class="btn btn-th btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                <?= Yii::t('modules/orders', 'orders.column.mode') ?>
                <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                <li class="<?= $searchModel->mode === null ? 'active' : '' ?>">
                    <a href="<?= $filterUrl('mode', null) ?>"><?= Yii::t('modules/orders', 'orders.filter.all') ?></a>
                </li>
                <li class="<?= (string)$searchModel->mode === (string)Order::MODE_MANUAL ? 'active' : '' ?>">
                    <a href="<?= $filterUrl('mode', (string)Order::MODE_MANUAL) ?>"><?= Yii::t('modules/orders', 'orders.mode.manual') ?></a>
                </li>
                <li class="<?= (string)$searchModel->mode === (string)Order::MODE_AUTO ? 'active' : '' ?>">
                    <a href="<?= $filterUrl('mode', (string)Order::MODE_AUTO) ?>"><?= Yii::t('modules/orders', 'orders.mode.auto') ?></a>
                </li>
            </ul>
        </div>
    </th>

    <th><?= Yii::t('modules/orders', 'orders.column.created') ?></th>
</tr>
