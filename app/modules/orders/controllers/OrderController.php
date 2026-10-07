<?php

namespace app\modules\orders\controllers;

use app\modules\orders\models\Order;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\Controller;
use app\modules\orders\models\OrderSearch;
use yii\web\NotFoundHttpException;
use yii\web\RangeNotSatisfiableHttpException;
use yii\web\Response;

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
                throw new NotFoundHttpException('Page not found.');
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

    /**
     * @throws InvalidConfigException
     * @throws RangeNotSatisfiableHttpException
     */
    public function actionExport($statusSlug = null)
    {
        // 1. Не дебажим этот запрос
        if (Yii::$app->has('debug')) {
            //Yii::$app->get('debug')->instance->allowedIPs = []; // Отключаем дебаг-панель для этого запроса
        }
        //Yii::$app->log->targets = []; // Полностью очищаем цели логирования в памяти

        // до перехода на массивы включенный дебаг и логирование забивали память, после перехода даже с ними нормально

        $searchModel = new OrderSearch();
        if ($statusSlug !== null && $statusSlug !== '') {
            $slugMap = Order::getStatusSlugMap();
            $searchModel->status = $slugMap[$statusSlug] ?? null;
        }

        $searchModel->search(Yii::$app->request->queryParams);

        // 2. Создаем временный поток
        $stream = fopen('php://temp', 'w+');
        fprintf($stream, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM для Excel

        // Заголовки столбцов CSV
        fputcsv($stream, ['ID', 'User', 'Link', 'Quantity', 'Service ID', 'Status', 'Mode', 'Created']);

        // 3. Используем asArray() для максимальной экономии памяти!
        // Никаких объектов ActiveRecord, только плоские массивы строк.
        $query = Order::find()
            ->select([
                'orders.id',
                'orders.link',
                'orders.quantity',
                'orders.service_id',
                'orders.status',
                'orders.mode',
                'orders.created_at',
                'CONCAT(users.first_name, " ", users.last_name) AS user_name' // Склеиваем имя в MySQL
            ])
            ->leftJoin('{{%users}} users', 'orders.user_id = users.id')
            ->filterBySearchModel($searchModel)
            ->asArray(); // <- ВАЖНО для Highload

        $statuses = [0 => 'Pending', 1 => 'In progress', 2 => 'Completed', 3 => 'Canceled', 4 => 'Error'];

        // Читаем пакетно по 500 строк. Память вообще не будет расти.
        foreach ($query->each(500) as $order) {
            fputcsv($stream, [
                $order['id'],
                $order['user_name'] ?? 'Guest',
                $order['link'],
                $order['quantity'],
                $order['service_id'],
                $statuses[$order['status']] ?? 'Unknown',
                $order['mode'] == 1 ? 'Auto' : 'Manual',
                date('Y-m-d H:i:s', $order['created_at'])
            ]);
        }

        rewind($stream);

        // 4. Отдаем поток как файл через чистый инструмент Yii2
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;

        $fileName = 'orders_export_' . date('Ymd_His') . '.csv';

        return $response->sendStreamAsFile($stream, $fileName, [
            'mimeType' => 'text/csv',
            'inline' => false,
        ]);
    }

}
