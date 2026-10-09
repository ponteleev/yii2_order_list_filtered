# Фильтруемый и локализованный листинг заказов (Yii2 Enterprise / Highload)

Высокооптимизированное Enterprise-приложение на фреймворке Yii2 для управления, фильтрации и пакетного экспорта заказов. Спроектировано с учетом SOLID-принципов для работы в условиях высоких highload-нагрузок.

---

## 📂 Архитектурная структура и карта проекта

Все ключевые компоненты системы декомпозированы по слоям ответственности и связаны с помощью относительных путей репозитория:

*   **Глобальный слой данных (Data Layer):**
    *   [app/models/Order.php](app/models/Order.php) — Базовая доменная модель заказов. Содержит строгие константы состояний (Status, Mode, SearchType) и карты слагов для ЧПУ.
    *   [app/models/query/OrderQuery.php](app/models/query/OrderQuery.php) — Кастомный класс ActiveQuery. Инкапсулирует в себе SQL-фильтры (`filterBySearchModel`, `getServicesSummary`) и полностью изолирован от веб-форм за счёт инверсии зависимостей (DIP).
    *   [app/models/contracts/OrderFilterInterface.php](app/models/contracts/OrderFilterInterface.php) — Абстрактный интерфейс-контракт данных. Разрывает циклическую зависимость между БД и UI-компонентами.

*   **Изолированный веб-модуль листинга заказов (`ModuleOrders`):**
    *   [Module.php](app/modules/orders/Module.php) — Класс инициализации модуля. Реализует `BootstrapInterface` для динамической регистрации собственных ЧПУ-правил роутинга и локального компонента `i18n`.
    *   [models/OrderSearch.php](app/modules/orders/models/OrderSearch.php) — Модель UI-поиска и HTTP-валидации входящих GET-параметров. Содержит декомпозированные методы вычисления каунтеров для дропдауна.
    *   [controllers/OrderController.php](app/modules/orders/controllers/OrderController.php) — Тонкий контроллер-диспетчер. Разгружен до декларативной карты внешних экшенов по SOLID.
    *   **Автономные классы экшенов (Action Layer):**
        *   [actions/IndexAction.php](app/modules/orders/actions/IndexAction.php) — Экшен обработки HTTP-запросов главной страницы и проброса отфильтрованных данных.
        *   [actions/ExportAction.php](app/modules/orders/actions/ExportAction.php) — Экшен-контроллер потокового скачивания файлов отчетов.
    *   **Локальные UI-хелперы:**
        *   [helpers/OrderUrlHelper.php](app/modules/orders/helpers/OrderUrlHelper.php) — Статический хелпер для безопасной генерации и изоляции ЧПУ-ссылок табов и выпадающих списков.

*   **Декомпозированный слой представления (Clean Views / Partials):**
    *   [index](app/modules/orders/views/order/index.php) — Легковесный диспетчер шаблонов, полностью очищенный от вычислительной логики.
    *   [_tabs_and_search](app/modules/orders/views/order/_tabs_and_search.php) — Панель навигационных табов статусов и формы поиска.
    *   [_table_header](app/modules/orders/views/order/_table_header.php) — Заголовки столбцов и выпадающие списки фильтров Service/Mode.
    *   [_table_row](app/modules/orders/views/order/_table_row.php) — Изолированный partial для безопасного экранирования и рендера одной строки данных.
    *   [_pagination](app/modules/orders/views/order/_pagination.php) — Пагинатор, каунтеры диапазона и кнопка экспорта.
    *   [error](app/modules/orders/views/order/error.php) — Выделенный шаблон красивого отображения 404/500 ошибок через `ErrorAction`.

*   **Глобальный сервисный слой (Service Layer):**
    *   [app/services/OrderExportService.php](app/services/OrderExportService.php) — Независимый сервис потокового формирования CSV-документов через курсорную выборку `each(500)`.

*   **Словари локализации (Key-Based i18n):**
    *   [orders/messages/en-US/orders.php](app/modules/orders/messages/en/orders.php) — Английский словарь кодовых системных ключей.
    *   [orders/messages/ru/orders.php](app/modules/orders/messages/ru/orders.php) — Русский словарь кодовых системных ключей.

*   **Инфраструктура и управление пакетами:**
    *   [app/composer.json](app/composer.json) — Конфигурация зависимостей. Настроена PSR-4 автозагрузка для вынесенных миграций и короткого неймспейса `ModuleOrders\\`.
    *   [app/config/web.php](app/config/web.php) — Чистый конфигурационный файл веб-приложения.
    *   [docker/docker-compose.yml](docker/docker-compose.yml) — Инфраструктурный манифест контейнеризации (PHP 8.2 + MySQL 8.0 + Nginx).

---

## ⚡ Индексная оптимизация СУБД MySQL 8.0 (Highload-карты)

Все поисковые и агрегирующие SQL-запросы приложения полностью покрыты B-Tree и префиксными индексами для предотвращения `Full Table Scan` и обеспечения работы в режиме `Using index` (~0.4 - 5.7 ms):

1.  `idx-orders-status-mode-service_id` — Трехкомпонентный составной индекс для моментальной фильтрации на активных вкладках статусов при выбранных параметрах Service и Mode.
2.  `idx-orders-status-service_id` — Двухкомпонентный индекс для защиты префикса при сбросе режима Mode в положение "Все".
3.  `idx-orders-mode-service_id` — Мощный составной индекс, разработанный специально для оптимизации тяжелого каунтера выпадающих списков на вкладке "Все заказы". Переводит `GROUP BY` в режим работы без временных дисковых таблиц (`Using index`).
4.  `idx-orders-status-link` — Составной B-Tree индекс со 100-символьным ограничением префикса (`status`, `link`(100)) для обеспечения моментальной фильтрации по диапазону (`type: range`, `Using index condition`) при поиске ссылок через левостороннюю маску `LIKE 'query%'`.
5.  `idx-users-first_name-last_name` — Составной индекс в таблице пользователей для высокоскоростного точного поиска по полному имени (`first_name`, `last_name`) со скоростью ~0.1 ms.

Полная история наката и структура DDL-операций с алгоритмами `INPLACE` без блокировок (`LOCK=NONE`) зафиксирована в каталоге миграций: [/migrations](/migrations).

---

## ⚙️ Установка и развертывание в Docker-окружении


1.  Склонируйте репозиторий и перейдите в подпапку инфраструктуры, скопируйте настройки окружения:
    ```bash
    cd docker
    cp .env_dist .env
    ```
    (Параметры портов, доменов и языков настраиваются гибко. По умолчанию задан `APP_LANGUAGE=en`).
2.  Разверните и запустите стек контейнеров в фоновом режиме:
    ```bash
    docker compose up -d
    ```
3.  Установите пакетные зависимости Composer и обновите карты автозагрузки PSR-4:
    ```bash
    docker compose exec php composer install
    docker compose exec php composer dump-autoload
    ```
4.  Примените вынесенные изолированные миграции базы данных в автоматическом режиме:
    ```bash
    docker compose exec php php yii migrate --interactive=0
    ```

После выполнения команд веб-интерфейс листинга заказов станет доступен по адресу: `http://localhost:8080/orders`.
