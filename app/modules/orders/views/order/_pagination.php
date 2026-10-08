<?php

declare(strict_types=1);

use yii\helpers\Url;
use yii\widgets\LinkPager;
use yii\data\ActiveDataProvider;

/**
 * @var ActiveDataProvider $dataProvider
 * @var string|null $statusSlug
 */
?>
<div class="row">
    <div class="col-sm-8">
        <?php if ($dataProvider->totalCount > $dataProvider->pagination->pageSize): ?>
            <?= LinkPager::widget([
                'pagination'           => $dataProvider->pagination,
                'options'              => ['class' => 'pagination'],
                'activePageCssClass'   => 'active',
                'disabledPageCssClass' => 'disabled',
                'prevPageLabel'        => '&laquo;',
                'nextPageLabel'        => '&raquo;',
                'hideOnSinglePage'     => true,
            ]) ?>
        <?php endif; ?>
    </div>

    <div class="col-sm-4 pagination-counters" style="padding-top: 25px;">
        <?php if ($dataProvider->totalCount > $dataProvider->pagination->pageSize): ?>
            <?= $dataProvider->getKeys() ? ($dataProvider->pagination->offset + 1) : 0 ?>
            <?= Yii::t('modules/orders', 'orders.pagination.to') ?>
            <?= $dataProvider->pagination->offset + count($dataProvider->getModels()) ?>
            <?= Yii::t('modules/orders', 'orders.pagination.of') ?>
            <?= $dataProvider->totalCount ?>
        <?php else: ?>
            <?= Yii::t('modules/orders', 'orders.pagination.total_records', ['count' => $dataProvider->totalCount]) ?>
        <?php endif; ?>

        <div style="margin-top: 5px;">
            <a href="<?= Url::to(array_merge($statusSlug === null ? ['/orders/order/export'] : ['/orders/order/export', 'statusSlug' => $statusSlug], Yii::$app->request->get())) ?>" class="text-primary">
                <span class="glyphicon glyphicon-save" aria-hidden="true"></span> <?= Yii::t('modules/orders', 'orders.export.save') ?>
            </a>
        </div>
    </div>
</div>
