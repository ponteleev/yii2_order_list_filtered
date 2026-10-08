<?php

namespace ModuleOrders;

use app\models\Order;
use yii\base\Application;
use yii\base\BootstrapInterface;

/**
 * Класс модуля управления заказами.
 * Реализует BootstrapInterface для автономной инкапсуляции правил ЧПУ.
 */
class Module extends \yii\base\Module implements BootstrapInterface
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'ModuleOrders\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        // custom initialization code goes here
    }

    /**
     * Вызывается автоматически на этапе загрузки приложения (Bootstrap).
     * Динамически инжектирует ЧПУ-правила модуля в глобальный UrlManager.
     *
     * @param Application $app Текущий экземпляр приложения
     * @return void
     */
    public function bootstrap($app): void
    {
        // Динамически собираем регулярное выражение из строгих констант домена
        $statusSlugsRegex = implode('|', [
            Order::SLUG_PENDING,
            Order::SLUG_IN_PROGRESS,
            Order::SLUG_COMPLETED,
            Order::SLUG_CANCELED,
            Order::SLUG_ERROR
        ]);

        // Добавляем инкапсулированные правила в глобальный urlManager
        $app->urlManager->addRules([
            // Полное ЧПУ: /orders/pending/manual/service-6
            "orders/<statusSlug:({$statusSlugsRegex})>/<mode:\d+>/service-<service_id:\d+>" => 'orders/order/index',

            // ЧПУ без режима: /orders/pending/service-6
            "orders/<statusSlug:({$statusSlugsRegex})>/service-<service_id:\d+>" => 'orders/order/index',

            // ЧПУ без сервиса: /orders/pending/manual
            "orders/<statusSlug:({$statusSlugsRegex})>/<mode:\d+>" => 'orders/order/index',

            // Базовые правила листинга
            "orders/<statusSlug:({$statusSlugsRegex})>" => 'orders/order/index',
            'orders' => 'orders/order/index',
        ], false); // Флаг false добавляет правила в начало стека (высший приоритет)
    }
}
