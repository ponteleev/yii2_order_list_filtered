<?php

use yii\db\Migration;

class m261006_105340_add_foreign_keys_to_orders_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Создаем индекс и внешний ключ для связи с таблицей пользователей
        $this->createIndex(
            '{{%idx-orders-user_id}}',
            '{{%orders}}',
            'user_id'
        );

        $this->addForeignKey(
            '{{%fk-orders-user_id}}',
            '{{%orders}}',
            'user_id',
            '{{%users}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        // 2. Создаем индекс и внешний ключ для связи с таблицей услуг
        $this->createIndex(
            '{{%idx-orders-service_id}}',
            '{{%orders}}',
            'service_id'
        );

        $this->addForeignKey(
            '{{%fk-orders-service_id}}',
            '{{%orders}}',
            'service_id',
            '{{%services}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        // Удаляем внешние ключи и индексы при откате миграции
        $this->dropForeignKey('{{%fk-orders-service_id}}', '{{%orders}}');
        $this->dropIndex('{{%idx-orders-service_id}}', '{{%orders}}');

        $this->dropForeignKey('{{%fk-orders-user_id}}', '{{%orders}}');
        $this->dropIndex('{{%idx-orders-user_id}}', '{{%orders}}');
    }
}
