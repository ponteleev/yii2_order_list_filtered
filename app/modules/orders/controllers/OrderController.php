<?php

namespace app\modules\orders\controllers;

use Yii;
use yii\web\Controller;
use app\modules\orders\models\OrderSearch;

class OrderController extends Controller
{
    // не перекрываем стандартный шаблон, а изолируем через лэйаут модуля
    public $layout = 'main';
    /**
     * Вывод списка заказов
     */
    public function actionIndex()
    {
        $searchModel = new OrderSearch();

        // Получаем провайдер данных с учетом GET-параметров фильтрации
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
}
