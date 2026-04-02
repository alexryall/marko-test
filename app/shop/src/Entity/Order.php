<?php

declare(strict_types=1);

namespace App\Shop\Entity;

use Marko\Database\Attributes\Column;
use Marko\Database\Attributes\Table;
use Marko\Database\Entity\Entity;

#[Table(name: 'orders')]
class Order extends Entity
{
    #[Column(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Column(length: 20, unique: true)]
    public string $reference;

    #[Column(length: 255)]
    public string $email;

    #[Column(length: 100)]
    public string $first_name;

    #[Column(length: 100)]
    public string $last_name;

    #[Column(length: 50)]
    public string $shipping_method;

    #[Column(type: 'integer')]
    public int $shipping_cost;

    #[Column(type: 'integer')]
    public int $subtotal;

    #[Column(type: 'integer')]
    public int $tax;

    #[Column(type: 'integer')]
    public int $total;

    #[Column(length: 30)]
    public string $created_at;

    public function formattedTotal(): string
    {
        return '$' . number_format($this->total / 100, 2);
    }

    public function formattedSubtotal(): string
    {
        return '$' . number_format($this->subtotal / 100, 2);
    }

    public function formattedTax(): string
    {
        return '$' . number_format($this->tax / 100, 2);
    }

    public function formattedShippingCost(): string
    {
        if ($this->shipping_cost === 0) {
            return 'Free';
        }

        return '$' . number_format($this->shipping_cost / 100, 2);
    }
}
