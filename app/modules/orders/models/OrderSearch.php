<?php

namespace app\modules\orders\models;

use yii\data\ActiveDataProvider;
use yii\db\Query;

class OrderSearch extends Order
{
    public $search;       // Строка поиска
    public $searchType;   // Тип поиска (1 - ID, 2 - Link, 3 - Username)
    public $foundUserIds = []; // массив пользователей при поисках по ним для дедубликации запросов

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

        // ТЗ + UX ТРАКТОВКА: Если отправлена строка поиска, но выпадающие фильтры
        // отсутствуют в GET-запросе (то есть это первое нажатие кнопки поиска) — сбрасываем их.
        // Если они есть в URL, значит, пользователь сужает текущий поиск, и мы их сохраняем!
        if (!empty($this->search) && !isset($params['mode']) && !isset($params['service_id'])) {
            $this->mode = null;
            $this->service_id = null;
        }

        // дедубликация перед вызовом OrderQuery->filterBySearchModel
        if (!empty($this->search) && $this->searchType == '3') {
            $this->foundUserIds = (new Query())
                ->select(['id'])
                ->from('{{%users}}')
                ->where(['like', 'first_name', $this->search])
                ->orWhere(['like', 'last_name', $this->search])
                ->column();
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
