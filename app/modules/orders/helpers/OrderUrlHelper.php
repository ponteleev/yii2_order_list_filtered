<?php

declare(strict_types=1);

namespace ModuleOrders\helpers;

use Yii;
use yii\helpers\Url;

/**
 * Хелпер для генерации и очистки ЧПУ-ссылок фильтрации заказов.
 */
class OrderUrlHelper
{
    /**
     * Генерирует корректный ЧПУ URL для фильтров с учетом сброса параметров.
     * Защищен от взаимного влияния параметров вкладки и фильтров (Bug-free).
     *
     * @param string $paramName Имя изменяемого параметра ('statusSlug', 'service_id', 'mode')
     * @param string|null $value Новое значение параметра (null для сброса)
     * @param string|null $currentStatusSlug Текущий активный слаг статуса из ЧПУ
     * @return string Валидный ЧПУ URL
     */
    public static function createFilterUrl(string $paramName, ?string $value, ?string $currentStatusSlug): string
    {
        // Получаем копию текущих GET параметров из запроса
        $getParams = Yii::$app->request->get();

        // КРИТИЧЕСКИ ВАЖНО: Удаляем автоматически распарсенный слаг текущей страницы,
        // чтобы он не накладывался на новые генерируемые ссылки в цикле.
        unset($getParams['statusSlug'], $getParams['status']);

        $route = ['/orders/order/index'];

        if ($paramName === 'statusSlug') {
            $activeSlug = $value;

            // Требование ревью: при переключении табов полностью уничтожаем все остальные фильтры
            unset(
                $getParams['mode'],
                $getParams['service_id'],
                $getParams['search'],
                $getParams['searchType'],
                $getParams['page']
            );
        } else {
            $activeSlug = $currentStatusSlug;

            // Сбрасываем страницу пагинации при смене мелких фильтров (Service/Mode)
            unset($getParams['page']);

            if ($value === null) {
                unset($getParams[$paramName]);
            } else {
                $getParams[$paramName] = $value;
            }
        }

        // Зашиваем слаг в тело ЧПУ-маршрута только если он передан и не пустой
        if ($activeSlug !== null && $activeSlug !== '') {
            $route['statusSlug'] = $activeSlug;
        }

        return Url::to(array_merge($route, $getParams));
    }
}
