<?php

namespace app\modules\orders\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\modules\orders\models\Order;

class OrderSearch extends Order
{
    public $search;       // Строка поиска
    public $searchType;   // Тип поиска (1 - ID, 2 - Link, 3 - Username)

    public function rules()
    {
        return [
            [['id', 'user_id', 'quantity', 'service_id', 'status', 'mode', 'created_at'], 'integer'],
            [['link', 'search', 'searchType'], 'safe'],
        ];
    }

    public function search($params)
    {
        // Base query для выборки самих данных (с джоинами)
        $query = Order::find()->joinWith(['user', 'service']);

        // КЛОН запроса для подсчета количества (без джоинов!)
        // Отключаем жадную загрузку связей строго для каунта
        $countQuery = Order::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 100,
            ],
            'sort' => [
                'defaultOrder' => ['id' => SORT_DESC],
            ],
        ]);

        $this->load($params, '');

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Применяем фильтры К ОБОИМ запросам одновременно
        $filterConditions = [
            'orders.status' => $this->status,
            'orders.mode' => $this->mode,
            'orders.service_id' => $this->service_id,
        ];

        $query->andFilterWhere($filterConditions);
        $countQuery->andFilterWhere($filterConditions);

        // Фильтр кастомного текстового поиска
        if (!empty($this->search)) {
            if ($this->searchType == '1') {
                $query->andWhere(['orders.id' => $this->search]);
                $countQuery->andWhere(['orders.id' => $this->search]);
            } elseif ($this->searchType == '2') {
                $query->andWhere(['like', 'orders.link', $this->search]);
                $countQuery->andWhere(['like', 'orders.link', $this->search]);
            } elseif ($this->searchType == '3') {
                // Для поиска по имени джоин к users НУЖЕН и в каунте
                $countQuery->joinWith(['user']);

                $query->andWhere(['like', 'users.first_name', $this->search])
                    ->orWhere(['like', 'users.last_name', $this->search]);
                $countQuery->andWhere(['like', 'users.first_name', $this->search])
                    ->orWhere(['like', 'users.last_name', $this->search]);
            }
        }

        // ИСПРАВЛЕНО: Явно передаем оптимизированный запрос подсчета в провайдер
        $dataProvider->totalCount = $countQuery->count();

        return $dataProvider;
    }
}
