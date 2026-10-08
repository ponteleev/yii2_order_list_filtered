<?php

declare(strict_types=1);

return [
    // Глобальные системные элементы и заголовки
    'orders.page.title'               => 'Orders',
    'orders.error.not_found'          => 'Page not found.',
    'orders.user.guest'               => 'Guest',

    // Вкладки навигации по статусам (Табы)
    'orders.tabs.all'                 => 'All orders',
    'orders.status.pending'           => 'Pending',
    'orders.status.in_progress'       => 'In progress',
    'orders.status.completed'         => 'Completed',
    'orders.status.canceled'          => 'Canceled',
    'orders.status.error'             => 'Error',

    // Элементы поисковой формы
    'orders.search.placeholder'       => 'Search orders',
    'orders.search.id'                => 'Order ID',
    'orders.search.link'              => 'Link',
    'orders.search.username'          => 'Username',

    // Элементы выпадающих фильтров (Mode / Service)
    'orders.filter.all'               => 'All',
    'orders.mode.manual'              => 'Manual',
    'orders.mode.auto'                => 'Auto',

    // Шапка таблицы и заголовки колонок экспорта CSV
    'orders.column.id'                => 'ID',
    'orders.column.user'              => 'User',
    'orders.column.link'              => 'Link',
    'orders.column.quantity'          => 'Quantity',
    'orders.column.service'           => 'Service',
    'orders.column.service_id'        => 'Service ID',
    'orders.column.status'            => 'Status',
    'orders.column.mode'              => 'Mode',
    'orders.column.created'           => 'Created',

    // Нижний блок: предлоги пагинации и кнопки управления
    'orders.pagination.to'            => 'to',
    'orders.pagination.of'            => 'of',
    'orders.pagination.total_records' => 'Total records: {count}',
    'orders.export.save'              => 'Save result',
];
