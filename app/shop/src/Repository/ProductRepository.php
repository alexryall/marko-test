<?php

declare(strict_types=1);

namespace App\Shop\Repository;

use App\Shop\Entity\Product;
use Marko\Database\Repository\Repository;

class ProductRepository extends Repository
{
    protected const string ENTITY_CLASS = Product::class;

    public function findBySlug(string $slug): ?Product
    {
        return $this->query()
            ->where('slug', '=', $slug)
            ->firstEntity();
    }

    public function findByCategory(string $category): array
    {
        return $this->query()
            ->where('category', '=', $category)
            ->orderBy('name')
            ->getEntities();
    }

    public function featured(int $limit = 6): array
    {
        return $this->query()
            ->orderBy('id')
            ->limit($limit)
            ->getEntities();
    }
}
