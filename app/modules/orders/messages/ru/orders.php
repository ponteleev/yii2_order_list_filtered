<?php

declare(strict_types=1);

return [
    // Глобальные системные элементы и заголовки
    'orders.page.title'               => 'Заказы',
    'orders.error.not_found'          => 'Страница не найдена.',
    'orders.user.guest'               => 'Гость',

    // Вкладки навигации по статусам (Табы)
    'orders.tabs.all'                 => 'Все заказы',
    'orders.status.pending'           => 'В ожидании',
    'orders.status.in_progress'       => 'В работе',
    'orders.status.completed'         => 'Завершен',
    'orders.status.canceled'          => 'Отменен',
    'orders.status.error'             => 'Ошибка',

    // Элементы поисковой формы
    'orders.search.placeholder'       => 'Поиск заказов',
    'orders.search.id'                => 'ID Заказа',
    'orders.search.link'              => 'Ссылка',
    'orders.search.username'          => 'Имя пользователя',

    // Элементы выпадающих фильтров (Mode / Service)
    'orders.filter.all'               => 'Все',
    'orders.mode.manual'              => 'Вручную',
    'orders.mode.auto'                => 'Авто',

    // Шапка таблицы и заголовки колонок экспорта CSV
    'orders.column.id'                => 'ID',
    'orders.column.user'              => 'Пользователь',
    'orders.column.link'              => 'Ссылка',
    'orders.column.quantity'          => 'Количество',
    'orders.column.service'           => 'Услуга',
    'orders.column.service_id'        => 'ID Услуги',
    'orders.column.status'            => 'Статус',
    'orders.column.mode'              => 'Режим',
    'orders.column.created'           => 'Создан',

    // Нижний блок: предлоги пагинации и кнопки управления
    'orders.pagination.to'            => 'из',
    'orders.pagination.of'            => 'для',
    'orders.pagination.total_records' => 'Всего записей: {count}',
    'orders.export.save'              => 'Сохранить результат',
];
