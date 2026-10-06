<?php

namespace app\modules\orders\controllers;

use app\modules\orders\models\Order;
use Yii;
use yii\web\Controller;
use app\modules\orders\models\OrderSearch;
use yii\web\NotFoundHttpException;

class OrderController extends Controller
{
    // не перекрываем стандартный шаблон, а изолируем через лэйаут модуля
    public $layout = 'main';
    /**
     * Вывод списка заказов
     * @throws NotFoundHttpException
     */
    public function actionIndex($statusSlug = null)
    {
        $searchModel = new OrderSearch();
        $queryParams = Yii::$app->request->queryParams;

        if ($statusSlug !== null && $statusSlug !== '') {
            $slugMap = Order::getStatusSlugMap();

            // Дополнительная защита: если ввели несуществующий слаг руками в URL
            if (isset($slugMap[$statusSlug])) {
                $searchModel->status = $slugMap[$statusSlug];
            } else {
                throw new \yii\web\NotFoundHttpException('Page not found.');
            }
        } else {
            // Если слаг пустой, принудительно делаем его null для корректной работы вью
            $statusSlug = null;
        }

        $dataProvider = $searchModel->search($queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'statusSlug' => $statusSlug, // Передаем активный слаг во вью
        ]);
    }
}
