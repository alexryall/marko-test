<?php

declare(strict_types=1);

namespace App\Shop\Controller;

use App\Shop\Repository\ProductRepository;
use App\Shop\Service\CartService;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Http\Response;
use Marko\View\ViewInterface;

class ProductController
{
    public function __construct(
        private ViewInterface $view,
        private ProductRepository $products,
        private CartService $cart,
    ) {}

    #[Get('/product/{slug}')]
    public function show(string $slug): Response
    {
        $product = $this->products->findBySlug($slug);

        if ($product === null) {
            return new Response('Product not found', 404);
        }

        $related = $this->products->featured(4);

        return $this->view->render('shop::pages/product', [
            'product' => $product,
            'related' => $related,
            'cartCount' => $this->cart->getCount(),
        ]);
    }
}
