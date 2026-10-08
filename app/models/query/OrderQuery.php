<?php

declare(strict_types=1);

namespace app\models\query;

use app\models\contracts\OrderFilterInterface;
use app\models\Order;
use yii\db\ActiveQuery;
use yii\db\Expression;

/**
 * Кастомный класс ActiveQuery для выполнения оптимизированных запросов к таблице заказов.
 *
 * @see Order
 */
class OrderQuery extends ActiveQuery
{
    /**
     * Накладывает условия фильтрации на базовый запрос на основе валидированной модели поиска.
     * Метод использует сильные составные индексы СУБД для обеспечения Highload-производительности.
     *
     * @param OrderFilterInterface $searchModel Реализация интерфейса модели поиска
     * @return self Текущий объект запроса для поддержки цепочки методов (Method Chaining)
     */
    public function filterBySearchModel(OrderFilterInterface $searchModel): self
    {
        // 1. Фильтрация по базовым составным индексам таблицы (status, mode, service_id)
        $this->andFilterWhere([
            'orders.status' => $searchModel->getStatus(),
            'orders.mode' => $searchModel->getMode(),
            'orders.service_id' => $searchModel->getServiceId(),
        ]);

        // 2. Применяем текстовый поиск через приватный метод
        $this->applySearchFilter($searchModel);

        return $this;
    }

    /**
     * Собирает агрегированную статистику (количество заказов) по сервисам с учетом текущих фильтров листинга.
     * Метод исключает из условий фильтрации само поле service_id, чтобы корректно рассчитать каунтеры для других пунктов.
     *
     * @param OrderFilterInterface $searchModel Реализация интерфейса модели поиска
     * @return array Сырой массив данных из СУБД в формате [['service_id' => X, 'count' => Y], ...]
     */
    public function getServicesSummary(OrderFilterInterface $searchModel): array
    {
        // Клонируем текущее состояние запроса
        $clone = clone $this;

        // Сбрасываем секции WHERE и JOIN, чтобы пересобрать легкий агрегирующий запрос.
        // Это гарантирует, что MySQL посчитает каунтеры строго по плоской таблице orders
        // и на 100% задействует составной индекс (Using index).
        $clone->where = null;
        $clone->join = null;

        // Повторяем базовую фильтрацию, исключая service_id
        $clone->andFilterWhere([
            'orders.status' => $searchModel->getStatus(),
            'orders.mode' => $searchModel->getMode(),
        ]);

        // Применяем изолированный текстовый поиск к клонированному объекту запроса
        $clone->applySearchFilter($searchModel);

        // Выполняем легкую агрегацию на стороне MySQL 8
        return $clone->select(['orders.service_id', 'COUNT(*) AS count'])
            ->groupBy(['orders.service_id'])
            ->asArray()
            ->all();
    }

    /**
     * Внутренний метод для изоляции дублирующейся логики текстового и идентификационного поиска.
     *
     * @param OrderFilterInterface $searchModel Реализация интерфейса модели поиска
     * @return void
     */
    private function applySearchFilter(OrderFilterInterface $searchModel): void
    {
        if (empty($searchModel->getSearch())) {
            return;
        }

        switch ((string)$searchModel->getSearchType()) {
            case Order::SEARCH_TYPE_ID: // Поиск по точному совпадению Order ID
                $this->andWhere(['orders.id' => $searchModel->getSearch()]);
                break;

            case Order::SEARCH_TYPE_LINK: // Поиск по частичному совпадению ссылки (Link) строго по префиксу
                // Третий параметр `false` указывает Yii2 не ставить процент в начале.
                // В итоге сгенерируется чистый SQL: WHERE orders.link LIKE 'значение%'
                //todo Обсудить с Андреем префиксный поиск, предложенный на ревью
                $this->andWhere(new Expression(
                    'orders.link LIKE :link',
                    [':link' => '%' . $searchModel->getSearch() . '%']
                ));
                break;

            case Order::SEARCH_TYPE_USERNAME: // Поиск по Username (first_name, last_name)
                // Используем предвыбранный методом интерфейса массив числовых ID пользователей (защита от N+1)
                if (!empty($searchModel->getFoundUserIds())) {
                    $this->andWhere(['orders.user_id' => $searchModel->getFoundUserIds()]);
                } else {
                    // Если пользователи по текстовой маске не найдены, принудительно режем запрос
                    $this->andWhere(['orders.user_id' => 0]);
                }
                break;
        }
    }
}
