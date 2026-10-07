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
        $this->load($params, '');

        // ТЗ: Если была отправлена строка поиска, сбрасываем mode и service_id
        if (!empty($this->search)) {
            $this->mode = null;
            $this->service_id = null;
        }

        $query = Order::find()->joinWith(['user', 'service']);
        $countQuery = Order::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 100],
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->filterBySearchModel($this);
        $countQuery->filterBySearchModel($this);

        $dataProvider->totalCount = $countQuery->count();

        return $dataProvider;
    }
}
