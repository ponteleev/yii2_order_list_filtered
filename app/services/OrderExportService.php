<?php

declare(strict_types=1);

namespace app\services;

use Yii;
use app\models\Order;
use app\models\contracts\OrderFilterInterface;
use yii\base\InvalidConfigException;

/**
 * Сервис для высокооптимизированного потокового экспорта заказов в CSV.
 */
class OrderExportService
{
    /**
     * Генерирует CSV-поток в памяти на основе переданного фильтра.
     * memory_limit удерживается строго в рамках 15-20 МБ.
     *
     * @param OrderFilterInterface $filter Реализация интерфейса параметров фильтрации
     * @return resource Временный дескриптор открытого файла php://temp
     * @throws InvalidConfigException
     */
    public function generateCsvStream(OrderFilterInterface $filter)
    {
        // Отключаем дебаг-панель Yii2 для полной разгрузки Logger из RAM
        if (Yii::$app->has('debug')) {
            Yii::$app->get('debug')->instance->allowedIPs = [];
        }
        Yii::$app->log->targets = [];

        // Открываем временный дескриптор в оперативной памяти PHP
        $stream = fopen('php://temp', 'w+');

        // Внедряем маркер BOM для корректного отображения кириллицы в MS Excel
        fprintf($stream, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Заголовки столбцов через новые кодовые ключи i18n
        fputcsv($stream, [
            Yii::t('modules/orders', 'orders.column.id'),
            Yii::t('modules/orders', 'orders.column.user'),
            Yii::t('modules/orders', 'orders.column.link'),
            Yii::t('modules/orders', 'orders.column.quantity'),
            Yii::t('modules/orders', 'orders.column.service_id'),
            Yii::t('modules/orders', 'orders.column.status'),
            Yii::t('modules/orders', 'orders.column.mode'),
            Yii::t('modules/orders', 'orders.column.created'),
        ]);

        // Построение запроса без ActiveRecord объектов через asArray
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
            ->filterBySearchModel($filter)
            ->orderBy(['orders.id' => SORT_DESC]) // Жесткая синхронизированная сортировка по ТЗ
            ->asArray();

        $statuses = [
            Order::STATUS_PENDING     => Yii::t('modules/orders', 'orders.status.pending'),
            Order::STATUS_IN_PROGRESS => Yii::t('modules/orders', 'orders.status.in_progress'),
            Order::STATUS_COMPLETED   => Yii::t('modules/orders', 'orders.status.completed'),
            Order::STATUS_CANCELED    => Yii::t('modules/orders', 'orders.status.canceled'),
            Order::STATUS_ERROR       => Yii::t('modules/orders', 'orders.status.error'),
        ];

        // Читаем курсором порциями по 500 строк (защита DoS по памяти)
        foreach ($query->each(500) as $order) {
            fputcsv($stream, [
                $order['id'],
                $order['user_name'] ?? Yii::t('modules/orders', 'orders.user.guest'),
                $order['link'],
                $order['quantity'],
                $order['service_id'],
                $statuses[(int)$order['status']] ?? 'Unknown',
                (int)$order['mode'] === Order::MODE_AUTO ? Yii::t('modules/orders', 'orders.mode.auto') : Yii::t('modules/orders', 'orders.mode.manual'),
                date('Y-m-d H:i:s', (int)$order['created_at'])
            ]);
        }

        rewind($stream);
        return $stream;
    }
}
