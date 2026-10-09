<?php

declare(strict_types=1);

namespace ModuleOrders\actions;

use Yii;
use yii\base\Action;
use yii\web\NotFoundHttpException;
use app\models\Order;
use ModuleOrders\models\OrderSearch;

/**
 * Экшен обработки главной страницы листинга и фильтрации заказов.
 * Избавлен от бизнес-логики и вычислительных алгоритмов (Thin Controller).
 */
class IndexAction extends Action
{
    /**
     * Выполняет обработку HTTP-запроса и рендеринг страницы.
     *
     * @param string|null $statusSlug Активный текстовый слаг статуса из ЧПУ-маршрута
     * @return string HTML-контент страницы
     * @throws NotFoundHttpException Если передан несуществующий слаг статуса
     */
    public function run(?string $statusSlug = null): string
    {
        $searchModel = new OrderSearch();
        $queryParams = Yii::$app->request->queryParams;

        // 1. Обработка ЧПУ-роутинга по слагам статусов в соответствии с ТЗ
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

        // 2. Инициализируем ActiveDataProvider для таблицы
        $dataProvider = $searchModel->search($queryParams);

        // 3. Извлекаем полностью подготовленные UI-данные каунтеров сервисов из модели
        $servicesData = $searchModel->getDropdownServicesData();

        // 4. Передаем чистые данные в шаблон представления диспетчера
        return $this->controller->render('index', [
            'searchModel'           => $searchModel,
            'dataProvider'          => $dataProvider,
            'statusSlug'            => $statusSlug,
            'dropdownServices'      => $servicesData['dropdownServices'],
            'totalAllServicesCount' => $servicesData['totalAllServicesCount'],
        ]);
    }
}
