<?php

namespace app\modules\orders\assets;

use yii\web\AssetBundle;

class OrdersAsset extends AssetBundle
{
    // Указываем Yii2, где физически лежат файлы модуля
    public $sourcePath = '@app/modules/orders/assets/web';

    public $css = [
        'css/bootstrap.min.css',
        'css/custom.css',
    ];
    public $js = [
        'js/jquery.min.js',
        'js/bootstrap.min.js',
    ];
}
