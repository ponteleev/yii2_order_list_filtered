<?php

namespace migrations;

use yii\db\Migration;

class m261007_084533_add_user_search_index_to_orders extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Создаем составной индекс Статус + Пользователь
        // Он идеально покроет поиск по Username внутри любых табов статусов
        $this->createIndex(
            'idx-orders-status-user_id',
            '{{%orders}}',
            ['status', 'user_id']
        );
    }

    public function safeDown()
    {
        $this->dropIndex('idx-orders-status-user_id', '{{%orders}}');
    }
}
