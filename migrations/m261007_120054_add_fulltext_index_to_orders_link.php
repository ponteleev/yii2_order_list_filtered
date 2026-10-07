<?php

namespace migrations;

use yii\base\NotSupportedException;
use yii\db\Migration;

class m261007_120054_add_fulltext_index_to_orders_link extends Migration
{
    /**
     * {@inheritdoc}
     * @throws NotSupportedException|\yii\db\Exception
     */
    public function safeUp()
    {
        // 1. БЕЗОПАСНОЕ УДАЛЕНИЕ: Проверяем, существует ли индекс в БД перед его удалением.
        // Это избавляет от необходимости городить громоздкие блоки try/catch.
        $tableSchema = $this->db->getSchema()->getTableSchema('{{%orders}}');

        if ($tableSchema !== null) {
            // Запрашиваем список всех существующих индексов таблицы через сырой SQL
            $indexes = $this->db->createCommand("SHOW INDEX FROM {{%orders}} WHERE Key_name = 'idx-orders-status-link'")->queryAll();

            if (!empty($indexes)) {
                $this->dropIndex('idx-orders-status-link', '{{%orders}}');
            }
        }

        // 2. Создаем полноценный FULLTEXT индекс на всё поле link
        $this->execute('ALTER TABLE {{%orders}} ADD FULLTEXT INDEX `idx-orders-link-fulltext` (`link`);');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-orders-link-fulltext', '{{%orders}}');
    }

}
