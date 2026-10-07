<?php

declare(strict_types=1);

namespace app\modules\orders\models;

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
     * @param OrderSearch $searchModel Экземпляр модели поиска с загруженными GET-параметрами
     * @return $this Текущий объект запроса для поддержки цепочки методов (Method Chaining)
     */
    public function filterBySearchModel(OrderSearch $searchModel): self
    {
        // 1. Фильтрация по базовым составным индексам таблицы (status, mode, service_id)
        $this->andFilterWhere([
            'orders.status' => $searchModel->status,
            'orders.mode' => $searchModel->mode,
            'orders.service_id' => $searchModel->service_id,
        ]);

        // 2. Применяем текстовый поиск через приватный метод
        $this->applySearchFilter($searchModel);

        return $this;
    }

    /**
     * Собирает агрегированную статистику (количество заказов) по сервисам с учетом текущих фильтров листинга.
     * Метод исключает из условий фильтрации само поле service_id, чтобы корректно рассчитать каунтеры для других пунктов.
     *
     * @param OrderSearch $searchModel Экземпляр модели поиска
     * @return array Сырой массив данных из СУБД в формате [['service_id' => X, 'count' => Y], ...]
     */
    public function getServicesSummary(OrderSearch $searchModel): array
    {
        // Клонируем текущее состояние запроса, чтобы не нарушить основной поток выборки данных в DataProvider
        $clone = clone $this;

        // Сбрасываем секцию WHERE у клона, чтобы пересобрать её без учета фильтра по service_id (требование ТЗ)
        $clone->where = null;

        // Повторяем базовую фильтрацию, исключая service_id
        $clone->andFilterWhere([
            'orders.status' => $searchModel->status,
            'orders.mode' => $searchModel->mode,
        ]);

        // Применяем изолированный текстовый поиск к клонированному объекту запроса
        $clone->applySearchFilter($searchModel);

        // Выполняем легкую агрегацию на стороне MySQL 8.
        return $clone->select(['orders.service_id', 'COUNT(*) AS count'])
            ->groupBy(['orders.service_id'])
            ->asArray()
            ->all();
    }

    /**
     * Внутренний метод для изоляции дублирующейся логики текстового и идентификационного поиска.
     *
     * @param OrderSearch $searchModel Экземпляр модели поиска
     * @return void
     */
    private function applySearchFilter(OrderSearch $searchModel): void
    {
        if (empty($searchModel->search)) {
            return;
        }

        switch ((string)$searchModel->searchType) {
            case '1': // Поиск по точному совпадению Order ID
                $this->andWhere(['orders.id' => $searchModel->search]);
                break;

            case '2': // Поиск по частичному совпадению ссылки (Link) через FULLTEXT
                // Используем BOOLEAN MODE для поиска подстроки
                $this->andWhere(new Expression(
                    'MATCH(orders.link) AGAINST(:search IN BOOLEAN MODE)',
                    [':search' => '*' . $searchModel->search . '*']
                ));
                break;

            case '3': // Поиск по Username (first_name, last_name)
                // Используем предвыбранный в модели OrderSearch массив числовых ID пользователей (защита от N+1)
                if (!empty($searchModel->foundUserIds)) {
                    $this->andWhere(['orders.user_id' => $searchModel->foundUserIds]);
                } else {
                    // Если пользователи по текстовой маске не найдены, принудительно режем запрос
                    $this->andWhere(['orders.user_id' => 0]);
                }
                break;
        }
    }
}
