<?php
/* @var $this \yii\web\View */
/* @var $content string */

use yii\helpers\Html;
use app\modules\orders\assets\OrdersAsset;

// Регистрируем ассет модуля
OrdersAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <?php $this->head() ?> <!-- Маркер для вставки CSS ассета -->
</head>
<body>
<?php $this->beginBody() ?> <!-- Маркер начала тела страницы -->

<?= $content ?> <!-- Сюда Yii автоматически подставит код из index.php -->

<?php $this->endBody() ?> <!-- Маркер для вставки JS ассета перед </body> -->
</body>
</html>
<?php $this->endPage() ?>

