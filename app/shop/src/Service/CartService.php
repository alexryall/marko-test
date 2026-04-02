<?php

declare(strict_types=1);

namespace App\Shop\Service;

use Marko\Session\Contracts\SessionInterface;

class CartService
{
    private const string CART_KEY = 'cart_items';

    public function __construct(
        private SessionInterface $session,
    ) {}

    public function getItems(): array
    {
        return $this->session->get(self::CART_KEY, []);
    }

    public function addItem(int $productId, string $name, int $price, string $size, string $color, string $imageUrl): void
    {
        $items = $this->getItems();
        $key = $productId . '-' . $size . '-' . $color;

        if (isset($items[$key])) {
            $items[$key]['quantity']++;
        } else {
            $items[$key] = [
                'product_id' => $productId,
                'name' => $name,
                'price' => $price,
                'size' => $size,
                'color' => $color,
                'image_url' => $imageUrl,
                'quantity' => 1,
            ];
        }

        $this->session->set(self::CART_KEY, $items);
    }

    public function updateQuantity(string $key, int $quantity): void
    {
        $items = $this->getItems();

        if ($quantity <= 0) {
            unset($items[$key]);
        } elseif (isset($items[$key])) {
            $items[$key]['quantity'] = $quantity;
        }

        $this->session->set(self::CART_KEY, $items);
    }

    public function removeItem(string $key): void
    {
        $items = $this->getItems();
        unset($items[$key]);
        $this->session->set(self::CART_KEY, $items);
    }

    public function clear(): void
    {
        $this->session->set(self::CART_KEY, []);
    }

    public function getCount(): int
    {
        $count = 0;

        foreach ($this->getItems() as $item) {
            $count += $item['quantity'];
        }

        return $count;
    }

    public function getSubtotal(): int
    {
        $total = 0;

        foreach ($this->getItems() as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return $total;
    }

    public function formattedSubtotal(): string
    {
        return '$' . number_format($this->getSubtotal() / 100, 2);
    }
}
