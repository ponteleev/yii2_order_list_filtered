<?php

namespace migrations;

use yii\db\Migration;

class m261006_102729_seed_data extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Определяем путь к файлу db_data.sql в той же папке
        $sqlFile = __DIR__ . '/db_data.sql';

        if (!file_exists($sqlFile)) {
            echo "Ошибка: Файл $sqlFile не найден!\n";
            return false;
        }

        // Читаем содержимое SQL файла
        $sql = file_get_contents($sqlFile);

        if (trim($sql) !== '') {
            echo "Начало импорта данных из db_data.sql...\n";

            // Выполняем весь SQL-дамп напрямую в MySQL 8
            $this->execute($sql);

            echo "Импорт данных успешно завершен.\n";

            return true;
        }
        return false;

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // При откате миграции очищаем таблицы в обратном порядке
        // (TRUNCATE сбрасывает в том числе счетчики AUTO_INCREMENT)

        // Отключаем проверку внешних ключей на время очистки
        $this->db->createCommand('SET FOREIGN_KEY_CHECKS = 0;')->execute();

        $this->truncateTable('{{%orders}}');
        $this->truncateTable('{{%users}}');
        $this->truncateTable('{{%services}}');

        return $this->db->createCommand('SET FOREIGN_KEY_CHECKS = 1;')->execute();
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m261006_102729_seed_data cannot be reverted.\n";

        return false;
    }
    */
}
