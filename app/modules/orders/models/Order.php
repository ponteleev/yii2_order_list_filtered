<?php

declare(strict_types=1);

namespace app\modules\orders\models;

use app\models\Users;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use app\models\User;
use app\models\Service;

/**
 * Класс модели для таблицы "{{%orders}}".
 *
 * @property int $id Идентификатор заказа
 * @property int $user_id Идентификатор пользователя
 * @property string $link Ссылка на объект заказа
 * @property int $quantity Количество
 * @property int $service_id Идентификатор услуги
 * @property int $status Статус заказа (0-Pending, 1-In progress, 2-Completed, 3-Canceled, 4-Error)
 * @property int $created_at Время создания (timestamp)
 * @property int $mode Режим выполнения (0-Manual, 1-Auto)
 *
 * @property User|null $user Связанная модель пользователя
 * @property Service|null $service Связанная модель услуги
 */
class Order extends ActiveRecord
{
    /**
     * Возвращает имя таблицы в базе данных.
     *
     * @return string Имя таблицы
     */
    public static function tableName(): string
    {
        return '{{%orders}}';
    }

    /**
     * Правила валидации полей модели.
     *
     * @return array Массив правил валидации
     */
    public function rules(): array
    {
        return [
            [['user_id', 'link', 'quantity', 'service_id', 'status', 'created_at', 'mode'], 'required'],
            [['user_id', 'quantity', 'service_id', 'status', 'created_at', 'mode'], 'integer'],
            [['link'], 'string', 'max' => 300],
            // Защита целостности данных: проверка существования внешних ключей в СУБД
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['service_id'], 'exist', 'skipOnError' => true, 'targetClass' => Service::class, 'targetAttribute' => ['service_id' => 'id']],
        ];
    }

    /**
     * Возвращает локализованные названия атрибутов (меток) полей.
     *
     * @return array Массив меток полей в формате [атрибут => название]
     */
    public function attributeLabels(): array
    {
        return [
            'id'         => Yii::t('modules/orders', 'ID'),
            'user_id'    => Yii::t('modules/orders', 'User ID'),
            'link'       => Yii::t('modules/orders', 'Link'),
            'quantity'   => Yii::t('modules/orders', 'Quantity'),
            'service_id' => Yii::t('modules/orders', 'Service ID'),
            'status'     => Yii::t('modules/orders', 'Status'),
            'created_at' => Yii::t('modules/orders', 'Created At'),
            'mode'       => Yii::t('modules/orders', 'Mode'),
        ];
    }

    /**
     * Карта соответствия ЧПУ-слагов URL и цифровых статусов в базе данных.
     *
     * @return array Ассоциативный массив слагов [слаг => статус_id]
     */
    public static function getStatusSlugMap(): array
    {
        return [
            'pending'     => 0,
            'in-progress' => 1,
            'completed'   => 2,
            'canceled'    => 3,
            'error'       => 4,
        ];
    }

    /**
     * Связь с моделью глобальных пользователей (Users).
     *
     * @return ActiveQuery
     */
    public function getUser(): ActiveQuery
    {
        return $this->hasOne(Users::class, ['id' => 'user_id']);
    }

    /**
     * Связь с моделью глобальных услуг (Services).
     *
     * @return ActiveQuery
     */
    public function getService(): ActiveQuery
    {
        return $this->hasOne(Service::class, ['id' => 'service_id']);
    }

    /**
     * Переопределяет стандартный фабричный метод создания запросов.
     * Подключает кастомный слой ActiveQuery фильтрации для Highload-задач.
     *
     * @return OrderQuery Мощный кастомный класс запросов модуля
     */
    public static function find(): OrderQuery
    {
        return new OrderQuery(static::class);
    }
}
