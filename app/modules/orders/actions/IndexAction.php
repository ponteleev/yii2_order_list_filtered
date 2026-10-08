<?php

declare(strict_types=1);

namespace ModuleOrders\actions;

use ModuleOrders\models\OrderSearch;
use Yii;
use yii\base\Action;
use yii\web\NotFoundHttpException;
use app\models\Order;

/**
 * Экшен обработки главной страницы листинга и фильтрации заказов.
 */
class IndexAction extends Action
{
    /**
     * Выполняет рендеринг страницы.
     * @throws NotFoundHttpException
     */
    public function run(?string $statusSlug = null): string
    {
        $searchModel = new OrderSearch();
        $queryParams = Yii::$app->request->queryParams;

        if ($statusSlug !== null && $statusSlug !== '') {
            $slugMap = Order::getStatusSlugMap();

            if (isset($slugMap[$statusSlug])) {
                $searchModel->status = $slugMap[$statusSlug];
            } else {
                throw new NotFoundHttpException(Yii::t('modules/orders', 'orders.error.not_found'));
            }
        } else {
            $statusSlug = null;
        }

        $dataProvider = $searchModel->search($queryParams);

        // Обращаемся к контроллеру для вызова метода рендера шаблона
        return $this->controller->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'statusSlug'   => $statusSlug,
        ]);
    }
}
