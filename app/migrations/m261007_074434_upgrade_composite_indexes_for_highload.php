<?php

use yii\db\Migration;

class m261007_074434_upgrade_composite_indexes_for_highload extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createIndex(
            'idx-orders-status-mode-service_id',
            '{{%orders}}',
            ['status', 'mode', 'service_id']
        );
    }

    public function safeDown()
    {
        $this->dropIndex('idx-orders-status-mode-service_id', '{{%orders}}');
    }
}
