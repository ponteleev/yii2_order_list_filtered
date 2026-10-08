<?php

declare(strict_types=1);

namespace ModuleOrders\controllers;

use ModuleOrders\actions\ExportAction;
use ModuleOrders\actions\IndexAction;
use yii\web\Controller;
use yii\web\ErrorAction;

/**
 * Тонкий контроллер-диспетчер модуля управления заказами.
 */
class OrderController extends Controller
{
    /**
     * @var string Указываем кастомный макет модуля заказов
     */
    public $layout = 'main';

    /**
     * Декларативная карта внешних независимых экшенов (Инкапсуляция по SOLID).
     *
     * @return array
     */
    public function actions(): array
    {
        return [
            'index'  => IndexAction::class,
            'export' => ExportAction::class,
            'error'  => [
                'class' => ErrorAction::class,
            ],
        ];
    }
}
