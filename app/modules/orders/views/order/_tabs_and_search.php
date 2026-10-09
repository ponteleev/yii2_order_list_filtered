<?php

declare(strict_types=1);

use ModuleOrders\helpers\OrderUrlHelper;
use ModuleOrders\models\OrderSearch;
use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Order;

/**
 * @var OrderSearch $searchModel
 * @var string|null $statusSlug Активный текстовый слаг статуса из ЧПУ
 */

$tabItems = [
    null                    => Yii::t('modules/orders', 'orders.tabs.all'),
    Order::SLUG_PENDING     => Yii::t('modules/orders', 'orders.status.pending'),
    Order::SLUG_IN_PROGRESS => Yii::t('modules/orders', 'orders.status.in_progress'),
    Order::SLUG_COMPLETED   => Yii::t('modules/orders', 'orders.status.completed'),
    Order::SLUG_CANCELED    => Yii::t('modules/orders', 'orders.status.canceled'),
    Order::SLUG_ERROR       => Yii::t('modules/orders', 'orders.status.error'),
];
?>

<ul class="nav nav-tabs p-b">
    <?php foreach ($tabItems as $slug => $label): ?>
        <li class="<?= $statusSlug === $slug ? 'active' : '' ?>">
            <a href="<?= OrderUrlHelper::createFilterUrl('statusSlug', $slug, $statusSlug) ?>"><?= Html::encode($label) ?></a>
        </li>
    <?php endforeach; ?>

    <li class="pull-right custom-search">
        <form class="form-inline" action="<?= Url::to($statusSlug === null ? ['/orders/order/index'] : ['/orders/order/index', 'statusSlug' => $statusSlug]) ?>" method="get">
            <div class="input-group">
                <input type="text" name="search" class="form-control" value="<?= Html::encode((string)$searchModel->search) ?>" placeholder="<?= Yii::t('modules/orders', 'orders.search.placeholder') ?>">
                <span class="input-group-btn search-select-wrap">
                    <select class="form-control search-select" name="searchType">
                        <option value="<?= Order::SEARCH_TYPE_ID ?>" <?= (string)$searchModel->searchType === Order::SEARCH_TYPE_ID ? 'selected' : '' ?>><?= Yii::t('modules/orders', 'orders.search.id') ?></option>
                        <option value="<?= Order::SEARCH_TYPE_LINK ?>" <?= (string)$searchModel->searchType === Order::SEARCH_TYPE_LINK ? 'selected' : '' ?>><?= Yii::t('modules/orders', 'orders.search.link') ?></option>
                        <option value="<?= Order::SEARCH_TYPE_USERNAME ?>" <?= (string)$searchModel->searchType === Order::SEARCH_TYPE_USERNAME ? 'selected' : '' ?>><?= Yii::t('modules/orders', 'orders.search.username') ?></option>
                    </select>
                    <button type="submit" class="btn btn-default">
                        <span class="glyphicon glyphicon-search" aria-hidden="true"></span>
                    </button>
                </span>
            </div>
        </form>
    </li>
</ul>
