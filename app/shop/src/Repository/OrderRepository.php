<?php

declare(strict_types=1);

namespace App\Shop\Repository;

use App\Shop\Entity\Order;
use Marko\Database\Repository\Repository;

class OrderRepository extends Repository
{
    protected const string ENTITY_CLASS = Order::class;

    public function findByReference(string $reference): ?Order
    {
        return $this->query()
            ->where('reference', '=', $reference)
            ->firstEntity();
    }
}
