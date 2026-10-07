<?php

namespace migrations;

use yii\db\Migration;

class m261007_115029_add_link_search_index_to_orders extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Создаем составной индекс: статус + первые 100 символов ссылки.
        // Это позволит MySQL выполнять фильтрацию подстроки на уровне индекса (ICP).
        $this->execute('ALTER TABLE {{%orders}} ADD INDEX `idx-orders-status-link` (`status`, `link`(100));');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-orders-status-link', '{{%orders}}');
    }
}
