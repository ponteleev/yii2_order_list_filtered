<?php

namespace migrations;

use yii\db\Migration;

class m261006_134733_add_index_to_services extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Создаем индекс на комбинацию (name, id).
        // Это позволит MySQL читать данные в уже отсортированном по алфавиту виде.
        // Указываем MySQL брать только первые 100 символов поля name для индекса.
        // 100 символов * 4 байта = 400 байт, что гарантированно пролезет в лимит 767 байт.
        $this->execute('ALTER TABLE {{%services}} ADD INDEX `idx-services-name-id` (`name`(100), `id`);');
    }

    public function safeDown()
    {
        $this->dropIndex('idx-services-name-id', '{{%services}}');
    }
}
