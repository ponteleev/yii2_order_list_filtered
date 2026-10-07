<?php

namespace migrations;

use yii\db\Migration;

class m261006_141859_add_idx_orders_status_service_id_to_orders extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Создаем один составной индекс, где status стоит на первом месте
        $this->createIndex(
            'idx-orders-status-service_id',
            '{{%orders}}',
            ['status', 'service_id']
        );
    }

    public function safeDown()
    {
        $this->dropIndex('idx-orders-status-service_id', '{{%orders}}');
    }
}
