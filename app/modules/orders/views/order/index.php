<?php

declare(strict_types=1);

use ModuleOrders\models\OrderSearch;
use yii\helpers\Html;
use yii\data\ActiveDataProvider;
use yii\web\View;

/**
 * Главный диспетчер листинга заказов.
 *
 * @var View $this
 * @var OrderSearch $searchModel Модель поиска и HTTP-валидации
 * @var ActiveDataProvider $dataProvider Провайдер данных с пагинацией
 * @var string|null $statusSlug Активный текстовый слаг статуса из ЧПУ
 * @var array $dropdownServices
 * @var int $totalAllServicesCount
 */

$this->title = Yii::t('modules/orders', 'orders.page.title');

?>

<div class="container-fluid">
    <!-- 1. Рендерим верхние табы и форму поиска -->
    <?= $this->render('_tabs_and_search', [
        'searchModel' => $searchModel,
        'statusSlug'  => $statusSlug,
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
            'statusSlug'  => $statusSlug,
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
