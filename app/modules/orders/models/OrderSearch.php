<?php

namespace ModuleOrders\models;

use app\models\contracts\OrderFilterInterface;
use app\models\Order;
use app\models\Service;
use yii\data\ActiveDataProvider;
use yii\db\Query;

class OrderSearch extends Order implements OrderFilterInterface
{
    public ?string $search = null;       // Строка поиска
    public ?string $searchType = null;   // Тип поиска (1 - ID, 2 - Link, 3 - Username)
    public array $foundUserIds = []; // массив пользователей при поисках по ним для дедубликации запросов

    /**
     * Строгие правила валидации и фильтрации входящих параметров поисковой UI-формы.
     * Защищают систему от XSS, переполнения RAM и несанкционированной подмены данных.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            [['search', 'searchType'], 'trim'],
            [['id', 'service_id'], 'integer', 'min' => 1],
            ['mode', 'in', 'range' => [Order::MODE_MANUAL, Order::MODE_AUTO]],
            ['status', 'in', 'range' => [
                Order::STATUS_PENDING, Order::STATUS_IN_PROGRESS, Order::STATUS_COMPLETED, Order::STATUS_CANCELED, Order::STATUS_ERROR
            ]],
            ['search', 'string', 'max' => 100],
            ['searchType', 'in', 'range' => [
                Order::SEARCH_TYPE_ID, Order::SEARCH_TYPE_LINK, Order::SEARCH_TYPE_USERNAME
            ]],
            ['searchType', 'required', 'when' => [$this, 'validateSearchTypeRequired']],
        ];
    }

    /**
     * Пользовательский метод проверки необходимости заполнения типа поиска.
     * Избавляет от ошибки "Serialization of 'Closure' is not allowed".
     */
    public function validateSearchTypeRequired(self $model): bool
    {
        return $model->search !== null && $model->search !== '';
    }

    /**
     * Инициализирует ActiveDataProvider и делегирует фильтрацию в ActiveQuery слой
     *
     * @param array $params Входящие GET-параметры запроса
     * @return ActiveDataProvider
     */
    public function search(array $params): ActiveDataProvider
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
            // Очищаем лишние пробелы по краям
            $trimmedSearch = trim($this->search);

            // Разделяем строку по пробелу на Имя и Фамилию
            $nameParts = explode(' ', $trimmedSearch, 2);

            $userQuery = (new Query())
                ->select(['id'])
                ->from('{{%users}}');

            if (count($nameParts) === 2) {
                // Если введены два слова (например, "Vicente Ochoa"), делаем строгое совпадение по обоим полям
                $userQuery->where([
                    'first_name' => trim($nameParts[0]),
                    'last_name'  => trim($nameParts[1]),
                ]);
            } else {
                // Если введено только одно слово (например, только "Vicente" или только "Ochoa"),
                // ищем строгое совпадение либо в имени, либо в фамилии
                $userQuery->where(['first_name' => $trimmedSearch])
                    ->orWhere(['last_name' => $trimmedSearch]);
            }

            $this->foundUserIds = $userQuery->column();
        }

        // 2. Инициализируем оптимизированные запросы (Count отдельно, Data отдельно)
        $countQuery = Order::find();
        $query = Order::find()->joinWith(['user', 'service']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 100,
                'pageSizeParam' => false,
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

    /**
     * Возвращает валидированный цифровой статус заказа для фильтрации.
     *
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return $this->status !== null ? (int)$this->status : null;
    }

    /**
     * Возвращает режим выполнения заказа (Manual/Auto).
     *
     * @return int|null
     */
    public function getMode(): ?int
    {
        return $this->mode !== null ? (int)$this->mode : null;
    }

    /**
     * Возвращает идентификатор выбранной услуги.
     *
     * @return int|null
     */
    public function getServiceId(): ?int
    {
        return $this->service_id !== null ? (int)$this->service_id : null;
    }

    /**
     * Возвращает очищенную текстовую строку поиска.
     *
     * @return string|null
     */
    public function getSearch(): ?string
    {
        return $this->search;
    }

    /**
     * Возвращает выбранный тип поиска (ID, Link, Username).
     *
     * @return string|null
     */
    public function getSearchType(): ?string
    {
        return $this->searchType;
    }

    /**
     * Возвращает предвыбранный массив ID пользователей (защита от N+1).
     *
     * @return array
     */
    public function getFoundUserIds(): array
    {
        return $this->foundUserIds;
    }

    /**
     * Возвращает полностью подготовленные данные для выпадающего списка сервисов.
     * Очищает экшены и представления от вычислительной логики.
     *
     * @return array Массив формата ['dropdownServices' => array, 'totalCount' => int]
     */
    public function getDropdownServicesData(): array
    {
        // Шаг 1. Получаем карту каунтеров из базы данных orders
        $serviceCounts = $this->fetchServiceCounts();

        // Шаг 2. Мержим каунтеры со статическим справочником из таблицы services
        $dropdownData = $this->buildDropdownItems($serviceCounts);

        // Шаг 3. Сортируем сервисы по ТЗ (от большего к меньшему)
        $dropdownServices = $this->sortDropdownItems($dropdownData['items']);

        return [
            'dropdownServices'      => $dropdownServices,
            'totalAllServicesCount' => $dropdownData['total'],
        ];
    }

    /**
     * Блок 1. Извлекает сырые агрегированные каунтеры заказов из СУБД.
     * Использует клон запроса и оптимизированные составные индексы.
     */
    private function fetchServiceCounts(): array
    {
        $queryInstance = Order::find();
        $queryInstance->filterBySearchModel($this);
        $stats = $queryInstance->getServicesSummary($this);

        $serviceCounts = [];
        foreach ($stats as $row) {
            $serviceCounts[(int)$row['service_id']] = (int)$row['count'];
        }

        return $serviceCounts;
    }

    /**
     * Блок 2. Формирует структуру для дропдауна на основе справочника Service.
     */
    private function buildDropdownItems(array $serviceCounts): array
    {
        $servicesData = Service::find()->asArray()->all();
        $items = [];
        $total = 0;

        foreach ($servicesData as $s) {
            $count = $serviceCounts[(int)$s['id']] ?? 0;
            $total += $count;

            $items[] = [
                'id'       => (int)$s['id'],
                'name'     => (string)$s['name'],
                'count'    => $count,
                'disabled' => ($count === 0),
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }

    /**
     * Блок 3. Выполняет лексикографическую сортировку элементов по убыванию каунтеров.
     */
    private function sortDropdownItems(array $items): array
    {
        usort($items, static function (array $a, array $b): int {
            return $b['count'] <=> $a['count'];
        });

        return $items;
    }

}
