<?php

declare(strict_types=1);

namespace App\Shop\Repository;

use App\Shop\Entity\OrderItem;
use Marko\Database\Repository\Repository;

class OrderItemRepository extends Repository
{
    protected const string ENTITY_CLASS = OrderItem::class;

    public function findByOrderId(int $orderId): array
    {
        return $this->query()
            ->where('order_id', '=', $orderId)
            ->getEntities();
    }
}
