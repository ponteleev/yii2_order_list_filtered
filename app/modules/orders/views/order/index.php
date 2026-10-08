<?php

declare(strict_types=1);

use ModuleOrders\models\OrderSearch;
use yii\helpers\Html;
use yii\helpers\Url;
use app\models\Order;
use app\models\Service;
use yii\data\ActiveDataProvider;
use yii\web\View;

/**
 * Главный диспетчер листинга заказов.
 *
 * @var View $this
 * @var OrderSearch $searchModel Модель поиска и HTTP-валидации
 * @var ActiveDataProvider $dataProvider Провайдер данных с пагинацией
 * @var string|null $statusSlug Активный текстовый слаг статуса из ЧПУ
 */

$this->title = Yii::t('modules/orders', 'orders.page.title');

// Универсальный генератор ЧПУ-ссылок для фильтров табов и выпадающих списков
$filterUrl = function(string $paramName, ?string $value) use ($statusSlug): string {
    $getParams = Yii::$app->request->get();
    $route = ['/orders/order/index'];

    if ($paramName === 'statusSlug') {
        $activeSlug = $value;
        // При смене таба полностью сбрасываем фильтры, поиск и пагинацию по требованию ревью
        unset($getParams['mode'], $getParams['service_id'], $getParams['search'], $getParams['searchType'], $getParams['page']);
    } else {
        $activeSlug = $statusSlug;
        unset($getParams['page']); // Сбрасываем страницу при смене мелких фильтров
        if ($value === null) {
            unset($getParams[$paramName]);
        } else {
            $getParams[$paramName] = $value;
        }
    }

    if ($activeSlug !== null && $activeSlug !== '') {
        $route['statusSlug'] = $activeSlug;
    }

    return Url::to(array_merge($route, $getParams));
};

// --- ВЫЧИСЛЕНИЕ ДИНАМИЧЕСКИХ КАУНТЕРОВ СЕРВИСОВ С УЧЕТОМ ТЗ ---
$queryInstance = Order::find();
$queryInstance->filterBySearchModel($searchModel);
$stats = $queryInstance->getServicesSummary($searchModel);

$serviceCounts = [];
foreach ($stats as $row) {
    $serviceCounts[(int)$row['service_id']] = (int)$row['count'];
}

$servicesData = Service::find()->asArray()->all();
$dropdownServices = [];
$totalAllServicesCount = 0; // Для исправления ошибки каунтера пункта All

foreach ($servicesData as $s) {
    $count = $serviceCounts[(int)$s['id']] ?? 0;
    $totalAllServicesCount += $count;
    $dropdownServices[] = [
        'id'       => (int)$s['id'],
        'name'     => (string)$s['name'],
        'count'    => $count,
        'disabled' => ($count === 0)
    ];
}

// Сортировка по ТЗ: от большего количества заказов к меньшему
usort($dropdownServices, function(array $a, array $b): int {
    return $b['count'] <=> $a['count'];
});
?>

<div class="container-fluid">
    <!-- 1. Рендерим верхние табы и форму поиска -->
    <?= $this->render('_tabs_and_search', [
        'searchModel' => $searchModel,
        'statusSlug'  => $statusSlug,
        'filterUrl'   => $filterUrl,
    ]) ?>
    <!-- Выводим блок ошибок валидации формы на экран (Bootstrap 3 alert) -->
    <?php if ($searchModel->hasErrors()): ?>
        <div class="alert alert-danger">
            <?= Html::errorSummary($searchModel, ['encode' => false]) ?>
        </div>
    <?php endif; ?>
    <!-- Таблица данных -->
    <table class="table order-table">
        <thead>
        <!-- 2. Рендерим заголовки таблицы и выпадающие списки фильтров -->
        <?= $this->render('_table_header', [
            'searchModel'           => $searchModel,
            'dropdownServices'      => $dropdownServices,
            'totalAllServicesCount' => $totalAllServicesCount,
            'filterUrl'             => $filterUrl,
        ]) ?>
        </thead>
        <tbody>
        <!-- 3. Рендерим строки заказов в цикле -->
        <?php foreach ($dataProvider->getModels() as $order): ?>
            <?= $this->render('_table_row', [
                'order' => $order,
            ]) ?>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- 4. Рендерим нижний блок пагинации, счетчиков и экспорта CSV -->
    <?= $this->render('_pagination', [
        'dataProvider' => $dataProvider,
        'statusSlug'   => $statusSlug,
    ]) ?>
</div>
