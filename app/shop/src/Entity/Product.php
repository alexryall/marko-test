<?php

declare(strict_types=1);

namespace App\Shop\Entity;

use Marko\Database\Attributes\Column;
use Marko\Database\Attributes\Table;
use Marko\Database\Entity\Entity;

#[Table(name: 'products')]
class Product extends Entity
{
    #[Column(primaryKey: true, autoIncrement: true)]
    public ?int $id = null;

    #[Column(length: 255)]
    public string $name;

    #[Column(length: 255, unique: true)]
    public string $slug;

    #[Column(type: 'text')]
    public string $description;

    #[Column(type: 'integer')]
    public int $price;

    #[Column(length: 100)]
    public string $category;

    #[Column(length: 500)]
    public string $image_url;

    #[Column(type: 'text')]
    public string $sizes;

    #[Column(type: 'text')]
    public string $colors;

    public function formattedPrice(): string
    {
        return '$' . number_format($this->price / 100, 2);
    }

    public function sizeList(): array
    {
        return json_decode($this->sizes, true) ?: [];
    }

    public function colorList(): array
    {
        return json_decode($this->colors, true) ?: [];
    }
}
