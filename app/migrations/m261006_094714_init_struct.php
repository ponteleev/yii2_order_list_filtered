<?php

use yii\db\Migration;

class m261006_094714_init_struct extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci';

        // 1. Таблица услуг (services)
        $this->createTable('{{%services}}', [
            'id' => $this->primaryKey(),
            // Указываем чистый SQL тип: кодировка идет ДО указания NOT NULL
            'name' => 'varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL',
        ], $tableOptions . ' ROW_FORMAT=COMPACT');

        $this->db->createCommand("ALTER TABLE {{%services}} AUTO_INCREMENT = 18;")->execute();

        // 2. Таблица пользователей (users)
        $this->createTable('{{%users}}', [
            'id' => $this->primaryKey(),
            'first_name' => 'varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL',
            'last_name' => 'varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL',
        ], $tableOptions . ' ROW_FORMAT=COMPACT');

        $this->db->createCommand("ALTER TABLE {{%users}} AUTO_INCREMENT = 101;")->execute();

        // 3. Таблица заказов (orders)
        $this->createTable('{{%orders}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'link' => 'varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_estonian_ci NOT NULL',
            'quantity' => $this->integer()->notNull(),
            'service_id' => $this->integer()->notNull(),
            'status' => $this->tinyInteger(1)->notNull()->comment('0 - Pending, 1 - In progress, 2 - Completed, 3 - Canceled, 4 - Fail'),
            'created_at' => $this->integer()->notNull(),
            'mode' => $this->tinyInteger(1)->notNull()->comment('0 - Manual, 1 - Auto'),
        ], $tableOptions . ' ROW_FORMAT=COMPACT');

        $this->db->createCommand("ALTER TABLE {{%orders}} AUTO_INCREMENT = 100001;")->execute();
    }

    public function safeDown()
    {
        $this->dropTable('{{%orders}}');
        $this->dropTable('{{%users}}');
        $this->dropTable('{{%services}}');
    }
}
