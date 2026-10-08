<?php

namespace migrations;

use yii\db\Migration;

class M261008083625AddCompositeModeServiceIndexToOrders extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Создаем мощный двухкомпонентный индекс для фильтрации по режиму и моментальной группировки по сервисам
        $this->createIndex(
            'idx-orders-mode-service_id',
            '{{%orders}}',
            ['mode', 'service_id']
        );
    }

    public function safeDown()
    {
        $this->dropIndex('idx-orders-mode-service_id', '{{%orders}}');
    }
}
