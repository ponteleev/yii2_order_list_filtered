<?php

namespace app\modules\orders\models;

use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[Order]].
 *
 * @see Order
 */
class OrderQuery extends ActiveQuery
{
    /**
     * {@inheritdoc}
     * @return Order[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return Order|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * Основная логика фильтрации
     */
    public function filterBySearchModel(OrderSearch $searchModel)
    {
        // Базовая фильтрация по статусу (всегда активна)
        $this->andFilterWhere(['orders.status' => $searchModel->status]);

        // Фильтрация по режиму и сервису
        $this->andFilterWhere(['orders.mode' => $searchModel->mode]);
        $this->andFilterWhere(['orders.service_id' => $searchModel->service_id]);

        // Поиск по строке
        if (!empty($searchModel->search)) {
            switch ($searchModel->searchType) {
                case '1':
                    $this->andWhere(['orders.id' => $searchModel->search]);
                    break;
                case '2':
                    $this->andWhere(['like', 'orders.link', $searchModel->search]);
                    break;
                case '3': // Поиск по Username
                    // Берем уже готовые ID, которые OrderSearch нашел за один раз
                    if (!empty($searchModel->foundUserIds)) {
                        $this->andWhere(['orders.user_id' => $searchModel->foundUserIds]);
                    } else {
                        // Если при поиске по буквам никто не нашелся или поиск пуст,
                        // сразу режем запрос, чтобы orders не сканировался зря
                        $this->andWhere(['orders.user_id' => 0]);
                    }
                    break;

            }
        }

        return $this;
    }

    /**
     * Специальный метод для сбора статистики по сервисам с учетом текущих фильтров (кроме самого сервиса)
     */
    public function getServicesSummary(OrderSearch $searchModel)
    {
        // Создаем чистый клон текущего запроса, игнорируя фильтр по service_id
        $clone = clone $this;

        // Очищаем WHERE от старого условия service_id, чтобы посчитать каунтеры для других пунктов
        if ($searchModel->service_id !== null) {
            $clone->where = ['and'];
            $clone->andFilterWhere(['orders.status' => $searchModel->status]);
            $clone->andFilterWhere(['orders.mode' => $searchModel->mode]);
            if (!empty($searchModel->search)) {
                // Повторяем условия поиска для клона
                if ($searchModel->searchType == '1') $clone->andWhere(['orders.id' => $searchModel->search]);
                if ($searchModel->searchType == '2') $clone->andWhere(['like', 'orders.link', $searchModel->search]);
                if ($searchModel->searchType == '3') {
                    $clone->joinWith(['user']);
                    $clone->andWhere(['or', ['like', 'users.first_name', $searchModel->search], ['like', 'users.last_name', $searchModel->search]]);
                }
            }
        }

        return $clone->select(['orders.service_id', 'COUNT(*) as count'])
            ->groupBy('orders.service_id')
            ->asArray()
            ->all();
    }
}
