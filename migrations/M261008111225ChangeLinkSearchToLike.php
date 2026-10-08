<?php

namespace migrations;

use yii\db\Migration;

class M261008111225ChangeLinkSearchToLike extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Удаляем ставший ненужным FULLTEXT индекс
        try {
            $this->dropIndex('idx-orders-link-fulltext', '{{%orders}}');
        } catch (\Exception $e) {}

        // 2. Создаем эффективный составной префиксный B-Tree индекс Статус + Ссылка (первые 100 символов)
        $this->execute('ALTER TABLE {{%orders}} ADD INDEX `idx-orders-status-link` (`status`, `link`(100));');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-orders-status-link', '{{%orders}}');
        $this->execute('ALTER TABLE {{%orders}} ADD FULLTEXT INDEX `idx-orders-link-fulltext` (`link`);');
    }
}
