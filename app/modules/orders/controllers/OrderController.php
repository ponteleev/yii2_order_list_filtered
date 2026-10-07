<?php

declare(strict_types=1);

namespace app\modules\orders\controllers;

use app\models\Order;
use app\modules\orders\models\OrderSearch;
use Yii;
use yii\base\InvalidConfigException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\RangeNotSatisfiableHttpException;
use yii\web\Response;

/**
 * OrderController управляет отображением списка заказов и процедурой экспорта.
 */
class OrderController extends Controller
{
    /**
     * @var string|bool Указываем кастомный макет (layout) модуля заказов
     */
    public $layout = 'main';

    /**
     * Отображает главную страницу модуля с таблицей заказов и фильтрами.
     *
     * @param string|null $statusSlug Человекочитаемый текстовый статус (слаг) из ЧПУ
     * @return string Текст отрендеренной страницы
     * @throws NotFoundHttpException Если передан некорректный или несуществующий слаг
     */
    public function actionIndex(?string $statusSlug = null): string
    {
        $searchModel = new OrderSearch();
        $queryParams = Yii::$app->request->queryParams;

        // Если слаг передан и не является пустой строкой
        if ($statusSlug !== null && $statusSlug !== '') {
            $slugMap = Order::getStatusSlugMap();

            // Защита: проверяем, существует ли такой текстовый статус в карте модуля
            if (isset($slugMap[$statusSlug])) {
                $searchModel->status = $slugMap[$statusSlug];
            } else {
                // Если слаг ввели руками и он некорректный — отдаем 404
                throw new NotFoundHttpException(Yii::t('modules/orders', 'Page not found.'));
            }
        } else {
            // Принудительно зануляем пустую строку для консистентного рендеринга табов во вью
            $statusSlug = null;
        }

        // Делегируем фильтрацию, сортировку и легкий COUNT в модель поиска
        $dataProvider = $searchModel->search($queryParams);

        return $this->render('index', [
            'searchModel'  => $searchModel,
            'dataProvider' => $dataProvider,
            'statusSlug'   => $statusSlug,
        ]);
    }

    /**
     * Выполняет высоко оптимизированный потоковый экспорт отфильтрованных заказов в CSV.
     * Запрос удерживает memory_limit строго ниже 128MB.
     *
     * @param string|null $statusSlug Человекочитаемый текстовый статус (слаг) из ЧПУ
     * @return Response Результат сырого ответа с потоковой передачей файла
     * @throws InvalidConfigException
     * @throws RangeNotSatisfiableHttpException
     */
    public function actionExport(?string $statusSlug = null): Response
    {
        // Отключаем дебаг-панель Yii2 для текущего запроса во избежание утечки памяти в Logger
        if (Yii::$app->has('debug')) {
            Yii::$app->get('debug')->instance->allowedIPs = [];
        }
        Yii::$app->log->targets = []; // Очищаем стек логирования в оперативной памяти

        $searchModel = new OrderSearch();
        if ($statusSlug !== null && $statusSlug !== '') {
            $slugMap = Order::getStatusSlugMap();
            $searchModel->status = $slugMap[$statusSlug] ?? null;
        }

        // Инициализируем свойства модели поиска на основе текущих GET-параметров
        $searchModel->search(Yii::$app->request->queryParams);

        // Открываем временный дескриптор в памяти PHP для генерации CSV контента
        $stream = fopen('php://temp', 'w+');

        // Внедряем маркер BOM для корректного отображения кириллического текста в MS Excel
        fprintf($stream, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Добавляем локализованные заголовки столбцов CSV через i18n
        fputcsv($stream, [
            Yii::t('modules/orders', 'ID'),
            Yii::t('modules/orders', 'User'),
            Yii::t('modules/orders', 'Link'),
            Yii::t('modules/orders', 'Quantity'),
            Yii::t('modules/orders', 'Service ID'),
            Yii::t('modules/orders', 'Status'),
            Yii::t('modules/orders', 'Mode'),
            Yii::t('modules/orders', 'Created'),
        ]);

        // Построение запроса: используем asArray() для полной разгрузки RAM от ActiveRecord объектов
        $query = Order::find()
            ->select([
                'orders.id',
                'orders.link',
                'orders.quantity',
                'orders.service_id',
                'orders.status',
                'orders.mode',
                'orders.created_at',
                'CONCAT(users.first_name, " ", users.last_name) AS user_name'
            ])
            ->leftJoin('{{%users}} users', 'orders.user_id = users.id')
            ->filterBySearchModel($searchModel)
            ->orderBy(['orders.id' => SORT_DESC])
            ->asArray();

        $statuses = [
            0 => Yii::t('modules/orders', 'Pending'),
            1 => Yii::t('modules/orders', 'In progress'),
            2 => Yii::t('modules/orders', 'Completed'),
            3 => Yii::t('modules/orders', 'Canceled'),
            4 => Yii::t('modules/orders', 'Error'),
        ];

        // Читаем данные курсором порциями по 500 строк
        foreach ($query->each(500) as $order) {
            fputcsv($stream, [
                $order['id'],
                $order['user_name'] ?? 'Guest',
                $order['link'],
                $order['quantity'],
                $order['service_id'],
                $statuses[(int)$order['status']] ?? 'Unknown',
                (int)$order['mode'] === 1 ? Yii::t('modules/orders', 'Auto') : Yii::t('modules/orders', 'Manual'),
                date('Y-m-d H:i:s', (int)$order['created_at'])
            ]);
        }

        // Возвращаем каретку потока на нулевой байт для чтения фреймворком
        rewind($stream);

        // Формируем принудительную скачку файла через встроенный стриминг ответов Yii2
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;

        $fileName = 'orders_export_' . date('Ymd_His') . '.csv';

        return $response->sendStreamAsFile($stream, $fileName, [
            'mimeType' => 'text/csv',
            'inline'   => false, // Принудительный заголовок Content-Disposition: attachment
        ]);
    }
}
