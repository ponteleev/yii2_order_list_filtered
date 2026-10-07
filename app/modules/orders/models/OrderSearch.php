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

    /**
     * Инициализирует ActiveDataProvider и делегирует фильтрацию в ActiveQuery слой
     *
     * @param array $params Входящие GET-параметры запроса
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        // 1. Загружаем входящие параметры из URL
        $this->load($params, '');

        // ТЗ: Если была отправлена строка поиска, сбрасываем mode и service_id
        if (!empty($this->search)) {
            $this->mode = null;
            $this->service_id = null;
        }

        // 2. Инициализируем оптимизированные запросы (Count отдельно, Data отдельно)
        $countQuery = Order::find();
        $query = Order::find()->joinWith(['user', 'service']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 100,
            ],
            'sort' => [
                'defaultOrder' => [
                    'id' => SORT_DESC,
                ]
            ],
        ]);

        // 3. Если валидация правил (rules) провалилась, возвращаем пустой результат по умолчанию
        if (!$this->validate()) {
            $dataProvider->totalCount = $countQuery->count();
            return $dataProvider;
        }

        // 4. Вызываем кастомный метод со всей логикой фильтрации
        $query->filterBySearchModel($this);
        $countQuery->filterBySearchModel($this);

        // Фиксируем точное оптимизированное количество строк для пагинатора
        $dataProvider->totalCount = $countQuery->count();

        return $dataProvider;
    }
}
