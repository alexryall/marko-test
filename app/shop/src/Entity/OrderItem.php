<?php

declare(strict_types=1);

namespace App\Shop\Entity;

use Marko\Database\Attributes\Column;
use Marko\Database\Attributes\Table;
use Marko\Database\Entity\Entity;

#[Table(name: 'order_items')]
class OrderItem extends Entity
{
    #[Column(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Column(type: 'integer', references: 'orders(id)', onDelete: 'CASCADE')]
    public int $order_id;

    #[Column(type: 'integer')]
    public int $product_id;

    #[Column(length: 255)]
    public string $product_name;

    #[Column(type: 'integer')]
    public int $price;

    #[Column(type: 'integer')]
    public int $quantity;

    #[Column(length: 50)]
    public string $size;

    #[Column(length: 50)]
    public string $color;

    public function formattedPrice(): string
    {
        return '$' . number_format($this->price / 100, 2);
    }

    public function lineTotal(): int
    {
        return $this->price * $this->quantity;
    }

    public function formattedLineTotal(): string
    {
        return '$' . number_format($this->lineTotal() / 100, 2);
    }
}
