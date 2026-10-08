<?php

namespace migrations;

use yii\db\Migration;

class M261008113344AddExactNameIndexesToUsers extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        // 0. Модернизируем формат хранения таблицы users.
        // Это снимает ограничение в 767 байт и расширяет лимит индексов до 3072 байт.
        $this->execute('ALTER TABLE {{%users}} ROW_FORMAT=DYNAMIC;');
        // 1. Создаем составной B-Tree индекс для быстрого поиска по полному имени (Имя + Фамилия).
        // Используем ALGORITHM=INPLACE и LOCK=NONE для обеспечения Zero-Downtime на продакшене.
        $this->execute('
            ALTER TABLE {{%users}} 
            ADD INDEX `idx-users-first_name-last_name` (`first_name`, `last_name`), 
            ALGORITHM=INPLACE, 
            LOCK=NONE;
        ');

        // 2. Создаем одиночный B-Tree индекс для фамилии на случай, если поиск идет только по ней.
        $this->execute('
            ALTER TABLE {{%users}} 
            ADD INDEX `idx-users-last_name` (`last_name`), 
            ALGORITHM=INPLACE, 
            LOCK=NONE;
        ');
    }

    public function safeDown()
    {
        // Удаляем созданные индексы чистыми SQL-командами
        $this->execute('ALTER TABLE {{%users}} DROP INDEX `idx-users-first_name-last_name`;');
        $this->execute('ALTER TABLE {{%users}} DROP INDEX `idx-users-last_name`;');
    }
}
