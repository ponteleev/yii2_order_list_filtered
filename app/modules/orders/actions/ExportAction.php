<?php

declare(strict_types=1);

namespace ModuleOrders\actions;

use ModuleOrders\models\OrderSearch;
use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\web\RangeNotSatisfiableHttpException;
use yii\web\Response;
use app\models\Order;
use app\services\OrderExportService;

/**
 * Экшен обработки запроса на потоковое скачивание отфильтрованного CSV-отчета.
 */
class ExportAction extends Action
{
    /**
     * Выполняет стриминг файла.
     * @throws RangeNotSatisfiableHttpException|InvalidConfigException
     */
    public function run(?string $statusSlug = null): Response
    {
        $searchModel = new OrderSearch();
        if ($statusSlug !== null && $statusSlug !== '') {
            $slugMap = Order::getStatusSlugMap();
            $searchModel->status = $slugMap[$statusSlug] ?? null;
        }

        $searchModel->search(Yii::$app->request->queryParams);

        // Инъецируем сервисный слой через стандартный конструктор/вызов
        $exportService = new OrderExportService();
        $stream = $exportService->generateCsvStream($searchModel);

        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;

        $fileName = 'orders_export_' . date('Ymd_His') . '.csv';

        return $response->sendStreamAsFile($stream, $fileName, [
            'mimeType' => 'text/csv',
            'inline'   => false,
        ]);
    }
}
