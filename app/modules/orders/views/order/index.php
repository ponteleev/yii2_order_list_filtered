<?php

use app\modules\orders\assets\OrdersAsset;
use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use app\modules\orders\models\Order;
use app\models\Service;
use yii\db\Query;

/* @var $this yii\web\View */
/* @var $searchModel app\modules\orders\models\OrderSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $statusSlug */

$this->title = 'Orders';


// Карта статусов
$statuses = [
    '0' => 'Pending',
    '1' => 'In progress',
    '2' => 'Completed',
    '3' => 'Canceled',
    '4' => 'Error'
];


// Переворачиваем карту, чтобы находить слаг по ID статуса (нужно для фильтрации сервисов)
$idToSlugMap = array_flip(Order::getStatusSlugMap());

/**
 * Универсальный генератор ЧПУ-ссылок для фильтров и табов
 *
 * @param string $paramName Имя изменяемого параметра ('statusSlug', 'service_id', 'mode')
 * @param mixed $value Новое значение параметра (null для сброса фильтра)
 * @return string Валидный URL
 */
$filterUrl = function(string $paramName, mixed $value) use ($statusSlug) {
    $getParams = Yii::$app->request->get();

    // Очищаем системный мусор и старые переменные
    unset($getParams['page'], $getParams['status'], $getParams['statusSlug']);

    // Базовый полный маршрут модуля
    $route = ['/orders/order/index'];

    // Вычисляем активный слаг статуса для этой ссылки
    if ($paramName === 'statusSlug') {
        $activeSlug = $value;
    } else {
        $activeSlug = $statusSlug;

        // Модифицируем GET-параметры для выпадающих списков (Service/Mode)
        if ($value === null) {
            unset($getParams[$paramName]);
        } else {
            $getParams[$paramName] = $value;
        }
    }

    // Если это вкладка "All orders" (null), параметр statusSlug вообще не должен генерироваться.
    if ($activeSlug !== null && $activeSlug !== '') {
        $route['statusSlug'] = $activeSlug;
    }

    return Url::to(array_merge($route, $getParams));
};

// Список табов для рендеринга
$tabItems = [
    null          => 'All orders',
    'pending'     => 'Pending',
    'in-progress' => 'In progress',
    'completed'   => 'Completed',
    'canceled'    => 'Canceled',
    'error'       => 'Error',
];

// Выбираем только те сервисы, по которым есть хотя бы один заказ.
// Используем asArray(), чтобы получить чистый массив строк вместо объектов.
// если в этом списке нужно отобразить ТОЛЬКО сервисы по отфильтрованным в других фильтрах заказам - расширим!
// 1. Получаем только уникальные ID сервисов, которые реально есть в заказах.
// Этот запрос отработает мгновенно по покрывающему индексу idx-orders-service_id (Using index)
$activeServiceIds = (new Query())
    ->select(['service_id'])
    ->from('{{%orders}}')
    ->distinct()
    ->column(); // Возвращает плоский массив [213, 214, 215...]

$activeServices = [];

// 2. Если заказы вообще есть, забираем имена только для этих ID
if (!empty($activeServiceIds)) {
    $activeServices = (new Query())
        ->select(['id', 'name'])
        ->from('{{%services}}')
        ->where(['id' => $activeServiceIds])
        ->orderBy(['name' => SORT_ASC])
        ->all();
}
?>
<style>
    .label-default {
        border: 1px solid #ddd;
        background: none;
        color: #333;
        min-width: 30px;
        display: inline-block;
    }
</style>

<!-- Навигация -->
<nav class="navbar navbar-fixed-top navbar-default">
    <div class="container-fluid">
        <div class="collapse navbar-collapse" id="bs-navbar-collapse">
            <ul class="nav navbar-nav">
                <li class="active"><a href="<?= Url::to(['/orders/order/index']) ?>">Orders</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid">

    <!-- Табы статусов -->
    <ul class="nav nav-tabs p-b">
        <?php foreach ($tabItems as $slug => $label): ?>
            <li class="<?= $statusSlug === $slug ? 'active' : '' ?>">
                <a href="<?= $filterUrl('statusSlug', $slug) ?>"><?= $label ?></a>
            </li>
        <?php endforeach; ?>

        <!-- Форма поиска -->
        <li class="pull-right custom-search">
            <!-- ИСПРАВЛЕНО: экшен формы теперь ведет на текущий ЧПУ-адрес, сохраняя статус -->
            <form class="form-inline" action="<?= Url::to($statusSlug === null ? ['/orders/order/index'] : ['/orders/order/index', 'statusSlug' => $statusSlug]) ?>" method="get">

                <!-- Скрытые поля для фильтров режима и сервиса -->
                <?= Yii::$app->request->get('mode') !== null ? Html::hiddenInput('mode', Yii::$app->request->get('mode')) : '' ?>
                <?= Yii::$app->request->get('service_id') !== null ? Html::hiddenInput('service_id', Yii::$app->request->get('service_id')) : '' ?>

                <div class="input-group">
                    <input type="text" name="search" class="form-control" value="<?= Html::encode(Yii::$app->request->get('search')) ?>" placeholder="Search orders">
                    <span class="input-group-btn search-select-wrap">
            <select class="form-control search-select" name="search-type">
              <option value="1" <?= Yii::$app->request->get('search-type') == '1' ? 'selected' : '' ?>>Order ID</option>
              <option value="2" <?= Yii::$app->request->get('search-type') == '2' ? 'selected' : '' ?>>Link</option>
              <option value="3" <?= Yii::$app->request->get('search-type') == '3' ? 'selected' : '' ?>>Username</option>
            </select>
            <button type="submit" class="btn btn-default">
                <span class="glyphicon glyphicon-search" aria-hidden="true"></span>
            </button>
          </span>
                </div>
            </form>
        </li>
    </ul>

    <!-- Таблица данных -->
    <table class="table order-table">
        <thead>
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>Link</th>
            <th>Quantity</th>

            <!-- Выпадающий фильтр: Service -->
            <th class="dropdown-th">
                <div class="dropdown">
                    <button class="btn btn-th btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                        Service <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li class="<?= $searchModel->service_id === null ? 'active' : '' ?>">
                            <!-- Выводим количество активных сервисов в списке (по которым есть заказы) -->
                            <a href="<?= $filterUrl('service_id', null) ?>">All (<?= count($activeServices) ?>)</a>
                        </li>
                        <?php foreach ($activeServices as $service): ?>
                            <li class="<?= $searchModel->service_id == $service['id'] ? 'active' : '' ?>">
                                <a href="<?= $filterUrl('service_id', $service['id']) ?>">
                                    <span class="label-id"><?= $service['id'] ?></span> <?= Html::encode($service['name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </th>

            <th>Status</th>

            <!-- Выпадающий фильтр: Mode -->
            <th class="dropdown-th">
                <div class="dropdown">
                    <button class="btn btn-th btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                        Mode <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu">
                        <li class="<?= $searchModel->mode === null ? 'active' : '' ?>">
                            <a href="<?= $filterUrl('mode', null) ?>">All</a>
                        </li>
                        <li class="<?= (string)$searchModel->mode === '0' ? 'active' : '' ?>">
                            <a href="<?= $filterUrl('mode', 0) ?>">Manual</a>
                        </li>
                        <li class="<?= (string)$searchModel->mode === '1' ? 'active' : '' ?>">
                            <a href="<?= $filterUrl('mode', 1) ?>">Auto</a>
                        </li>
                    </ul>
                </div>
            </th>

            <th>Created</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($dataProvider->getModels() as $order): ?>
            <tr>
                <td><?= $order->id ?></td>
                <td><?= Html::encode($order->user ? $order->user->first_name . ' ' . $order->user->last_name : 'Guest') ?></td>
                <td class="link"><?= Html::encode($order->link) ?></td>
                <td><?= $order->quantity ?></td>
                <td class="service">
                    <span class="label-id"><?= $order->service_id ?></span> <?= Html::encode($order->service ? $order->service->name : '') ?>
                </td>
                <td><?= isset($statuses[$order->status]) ? ($statuses[$order->status] == 'Fail' ? 'Error' : $statuses[$order->status]) : 'Unknown' ?></td>
                <td><?= $order->mode == 1 ? 'Auto' : 'Manual' ?></td>
                <td>
                    <span class="nowrap"><?= Yii::$app->formatter->asDate($order->created_at, 'yyyy-MM-dd') ?></span>
                    <span class="nowrap"><?= Yii::$app->formatter->asTime($order->created_at, 'HH:mm:ss') ?></span>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Пагинация -->
    <div class="row">
        <div class="col-sm-8">
            <?= LinkPager::widget([
                'pagination' => $dataProvider->pagination,
                'options' => ['class' => 'pagination'],
                'activePageCssClass' => 'active',
                'disabledPageCssClass' => 'disabled',
                'prevPageLabel' => '&laquo;',
                'nextPageLabel' => '&raquo;',
            ]) ?>
        </div>
        <div class="col-sm-4 pagination-counters" style="padding-top: 25px;">
            <?= $dataProvider->getKeys() ? ($dataProvider->pagination->offset + 1) : 0 ?>
            to
            <?= $dataProvider->pagination->offset + count($dataProvider->getModels()) ?>
            of
            <?= $dataProvider->totalCount ?>
        </div>
    </div>
</div>
