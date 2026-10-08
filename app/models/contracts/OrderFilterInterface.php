<?php

declare(strict_types=1);

namespace app\models\contracts;

/**
 * Интерфейс контракта для фильтрации заказов.
 * Позволяет любому объекту (веб-форме, DTO, консольной команде) выступать в роли фильтра.
 */
interface OrderFilterInterface
{
    public function getStatus(): ?int;
    public function getMode(): ?int;
    public function getServiceId(): ?int;
    public function getSearch(): ?string;
    public function getSearchType(): ?string;
    public function getFoundUserIds(): array;
}
