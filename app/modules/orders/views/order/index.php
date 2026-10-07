<?php

declare(strict_types=1);

use yii\helpers\Url;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\LinkPager;
use app\models\Service;
use app\modules\orders\models\Order;
use app\modules\orders\models\OrderSearch;
use yii\data\ActiveDataProvider;

/**
 * Файл представления (view) для отображения листинга заказов.
 *
 * @var View $this
 * @var OrderSearch $searchModel Модель поиска и HTTP-валидации
 * @var ActiveDataProvider $dataProvider Провайдер данных с пагинацией по 100 записей
 * @var string|null $statusSlug Активный текстовый слаг статуса из ЧПУ-роутинга
 */

$this->title = Yii::t('modules/orders', 'Orders');

// Переворачиваем карту для URL-генерации (чтобы при необходимости находить слаг по ID)
$idToSlugMap = array_flip(Order::getStatusSlugMap());

/**
 * Универсальный генератор ЧПУ-ссылок для фильтров табов и выпадающих списков.
 * Бережно сохраняет параметры поиска при переключении фильтров.
 *
 * @param string $paramName Имя изменяемого параметра ('statusSlug', 'service_id', 'mode')
 * @param mixed $value Новое значение параметра (null для сброса фильтра)
 * @return string Валидный ЧПУ URL
 */
$filterUrl = function(string $paramName, ?string $value) use ($statusSlug): string {
    $getParams = Yii::$app->request->get();

    // Принудительно вырезаем системный мусор и старые GET-параметры
    unset($getParams['page'], $getParams['status'], $getParams['statusSlug']);

    $route = ['/orders/order/index'];

    if ($paramName === 'statusSlug') {
        $activeSlug = $value;
        // ТЗ: При переключении таба статуса, фильтры mode и service сбрасываются
        unset($getParams['mode'], $getParams['service_id']);
    } else {
        $activeSlug = $statusSlug;
        if ($value === null) {
            unset($getParams[$paramName]);
        } else {
            $getParams[$paramName] = $value;
        }
    }

    // Если слаг активен и не пустой, зашиваем его в тело ЧПУ-маршрута для генерации /orders/pending
    if ($activeSlug !== null && $activeSlug !== '') {
        $route['statusSlug'] = $activeSlug;
    }

    return Url::to(array_merge($route, $getParams));
};

// Получаем сырую агрегированную статистику из нашего кастомного ActiveQuery метода.
// Этот запрос отрабатывает по новому составному индексу мгновенно (Using index).
$queryInstance = Order::find();
$queryInstance->filterBySearchModel($searchModel);
$stats = $queryInstance->getServicesSummary($searchModel);

// Схлопываем результат в плоский ассоциативный массив [service_id => count]
$serviceCounts = [];
foreach ($stats as $row) {
    $serviceCounts[(int)$row['service_id']] = (int)$row['count'];
}

// Загружаем справочник всех сервисов (используем asArray() для экономии памяти)
$servicesData = Service::find()->asArray()->all();
$dropdownServices = [];

// Формируем массив для выпадающего списка согласно жестким требованиям ТЗ
foreach ($servicesData as $s) {
    $count = $serviceCounts[(int)$s['id']] ?? 0;
    $dropdownServices[] = [
        'id'       => (int)$s['id'],
        'name'     => (string)$s['name'],
        'count'    => $count,
        'disabled' => ($count === 0) // Серый цвет для нулевых результатов по ТЗ
    ];
}

// Сортировка по ТЗ: от большего количества заказов к меньшему
usort($dropdownServices, function(array $a, array $b): int {
    return $b['count'] <=> $a['count'];
});

// Локализованные списки статусов и табов навигации
$statusesMap = [
    0 => Yii::t('modules/orders', 'Pending'),
    1 => Yii::t('modules/orders', 'In progress'),
    2 => Yii::t('modules/orders', 'Completed'),
    3 => Yii::t('modules/orders', 'Canceled'),
    4 => Yii::t('modules/orders', 'Error')
];

$tabItems = [
    null          => Yii::t('modules/orders', 'All orders'),
    'pending'     => Yii::t('modules/orders', 'Pending'),
    'in-progress' => Yii::t('modules/orders', 'In progress'),
    'completed'   => Yii::t('modules/orders', 'Completed'),
    'canceled'    => Yii::t('modules/orders', 'Canceled'),
    'error'       => Yii::t('modules/orders', 'Error'),
];
?>

<div class="container-fluid">
    <!-- Табы статусов (Навигация по ТЗ) -->
    <ul class="nav nav-tabs p-b">
        <?php foreach ($tabItems as $slug => $label): ?>
            <li class="<?= $statusSlug === $slug ? 'active' : '' ?>">
                <a href="<?= $filterUrl('statusSlug', $slug) ?>"><?= Html::encode($label) ?></a>
            </li>
        <?php endforeach; ?>

        <!-- Форма поиска (По ТЗ сбрасывает мелкие фильтры при отправке) -->
        <li class="pull-right custom-search">
            <form class="form-inline" action="<?= Url::to($statusSlug === null ? ['/orders/order/index'] : ['/orders/order/index', 'statusSlug' => $statusSlug]) ?>" method="get">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" value="<?= Html::encode((string)$searchModel->search) ?>" placeholder="<?= Yii::t('modules/orders', 'Search orders') ?>">
                    <span class="input-group-btn search-select-wrap">
            <select class="form-control search-select" name="searchType">
              <option value="1" <?= (string)$searchModel->searchType === '1' ? 'selected' : '' ?>><?= Yii::t('modules/orders', 'Order ID') ?></option>
              <option value="2" <?= (string)$searchModel->searchType === '2' ? 'selected' : '' ?>><?= Yii::t('modules/orders', 'Link') ?></option>
              <option value="3" <?= (string)$searchModel->searchType === '3' ? 'selected' : '' ?>><?= Yii::t('modules/orders', 'Username') ?></option>
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
            <th><?= Yii::t('modules/orders', 'ID') ?></th>
            <th><?= Yii::t('modules/orders', 'User') ?></th>
            <th><?= Yii::t('modules/orders', 'Link') ?></th>
            <th><?= Yii::t('modules/orders', 'Quantity') ?></th>

            <!-- Фильтр: Service (Динамические каунтеры и сортировка по ТЗ) -->
            <th class="dropdown-th">
                <div class="dropdown">
                    <button class="btn btn-th btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                        <?= Yii::t('modules/orders', 'Service') ?>
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                        <li class="<?= $searchModel->service_id === null ? 'active' : '' ?>">
                            <a href="<?= $filterUrl('service_id', null) ?>"><?= Yii::t('modules/orders', 'All') ?> (<?= $dataProvider->totalCount ?>)</a>
                        </li>
                        <?php foreach ($dropdownServices as $item): ?>
                            <?php if ($item['disabled']): ?>
                                <!-- ТЗ: Если результатов нет с учетом других полей, пункт недоступен и подсвечен серым -->
                                <li class="grey disabled" style="padding: 3px 20px; color: #c1c1c1; cursor: not-allowed;">
                                    <span class="label-id" style="border-color: #eee;"><?= $item['id'] ?></span>
                                    <?= Html::encode($item['name']) ?> (0)
                                </li>
                            <?php else: ?>
                                <li class="(int)$searchModel->service_id === $item['id'] ? 'active' : '' ?>">
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

            <th><?= Yii::t('modules/orders', 'Status') ?></th>

            <!-- Фильтр: Mode -->
            <th class="dropdown-th">
                <div class="dropdown">
                    <button class="btn btn-th btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                        <?= Yii::t('modules/orders', 'Mode') ?>
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
                        <li class="<?= $searchModel->mode === null ? 'active' : '' ?>">
                            <a href="<?= $filterUrl('mode', null) ?>"><?= Yii::t('modules/orders', 'All') ?></a>
                        </li>
                        <li class="<?= (string)$searchModel->mode === '0' ? 'active' : '' ?>">
                            <a href="<?= $filterUrl('mode', '0') ?>"><?= Yii::t('modules/orders', 'Manual') ?></a>
                        </li>
                        <li class="<?= (string)$searchModel->mode === '1' ? 'active' : '' ?>">
                            <a href="<?= $filterUrl('mode', '1') ?>"><?= Yii::t('modules/orders', 'Auto') ?></a>
                        </li>
                    </ul>
                </div>
            </th>

            <th><?= Yii::t('modules/orders', 'Created') ?></th>
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
                <span class="label-id"><?= $order->service_id ?></span>
                <?= Html::encode($order->service ? $order->service->name : '') ?>
            </td>
            <td><?= Html::encode($statusesMap[(int)$order->status] ?? 'Unknown') ?></td>
            <td><?= (int)$order->mode === 1 ? Yii::t('modules/orders', 'Auto') : Yii::t('modules/orders', 'Manual') ?></td>
            <td>
                <span class="nowrap"><?= Yii::$app->formatter->asDate((int)$order->created_at, 'yyyy-MM-dd') ?></span>
                <span class="nowrap"><?= Yii::$app->formatter->asTime((int)$order->created_at, 'HH:mm:ss') ?></span>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Нижний блок навигации и пагинации (Логика отображения строго по ТЗ) -->
    <div class="row">
        <div class="col-sm-8">
            <?php
            // ТЗ: Отображаем сетку страниц только в том случае, если записей больше, чем вмещает одна страница (100 штук)
            if ($dataProvider->totalCount > $dataProvider->pagination->pageSize):
                ?>
                <?= LinkPager::widget([
                'pagination'         => $dataProvider->pagination,
                'options'            => ['class' => 'pagination'],
                'activePageCssClass' => 'active',
                'disabledPageCssClass' => 'disabled',
                'prevPageLabel'      => '&laquo;',
                'nextPageLabel'      => '&raquo;',
                'hideOnSinglePage'   => true, // Встроенная защита Yii2 для скрытия на одной странице
            ]) ?>
            <?php endif; ?>
        </div>

        <div class="col-sm-4 pagination-counters" style="padding-top: 25px;">
            <?php if ($dataProvider->totalCount > $dataProvider->pagination->pageSize): ?>
                <!-- Если страниц много: выводим текстовый диапазон с локализацией предлогов -->
                <?= $dataProvider->getKeys() ? ($dataProvider->pagination->offset + 1) : 0 ?>
                <?= Yii::t('modules/orders', 'to') ?>
                <?= $dataProvider->pagination->offset + count($dataProvider->getModels()) ?>
                <?= Yii::t('modules/orders', 'of') ?>
                <?= $dataProvider->totalCount ?>
            <?php else: ?>
                <!-- ТЗ: Если количество записей помещается на 1 странице, выводим просто общее количество записей -->
                <?= Yii::t('modules/orders', 'Total records: {count}', ['count' => $dataProvider->totalCount]) ?>
            <?php endif; ?>

            <!-- Ссылка на потоковое скачивание CSV-отчета -->
            <div style="margin-top: 5px;">
                <a href="<?= Url::to(array_merge($statusSlug === null ? ['/orders/order/export'] : ['/orders/order/export', 'statusSlug' => $statusSlug], Yii::$app->request->get())) ?>" class="text-primary">
                    <span class="glyphicon glyphicon-save" aria-hidden="true"></span> <?= Yii::t('modules/orders', 'Save result') ?>
                </a>
            </div>
        </div>
    </div>
</div>
