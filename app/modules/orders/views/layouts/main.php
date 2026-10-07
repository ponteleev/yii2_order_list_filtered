<?php
/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use app\modules\orders\assets\OrdersAsset;
use yii\helpers\Url;

/**
 * Главный шаблон (layout) модуля управления заказами.
 * Обеспечивает сквозную глобальную навигацию по ТЗ и мультиязычность.
 *
 * @var \yii\web\View $this
 * @var string $content Содержимое дочернего представления (вьюхи index.php)
 */

// Регистрация мета-тегов и CSRF для защиты от подделки межсайтовых запросов
$this->registerCsrfMetaTags();
// Регистрируем ассет модуля
OrdersAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Html::encode(Yii::$app->language) ?>">
<head>
    <meta charset="<?= Html::encode(Yii::$app->charset) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<!-- Глобальная верхняя панель навигации  -->
<nav class="navbar navbar-fixed-top navbar-default">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <!-- Ссылки глобального меню с поддержкой i18n локализации -->
            <ul class="nav navbar-nav">
                <li class="active">
                    <a href="<?= Url::to(['/orders/order/index']) ?>">
                        <?= Yii::t('modules/orders', 'Orders') ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Контейнер для динамического вывода контента вьюхи (index.php) -->
<div class="main-content">
    <?= $content ?>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

