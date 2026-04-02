<?php

declare(strict_types=1);

namespace App\Shop\Controller;

use App\Shop\Repository\ProductRepository;
use App\Shop\Service\CartService;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Http\Response;
use Marko\View\ViewInterface;

class CategoryController
{
    public function __construct(
        private ViewInterface $view,
        private ProductRepository $products,
        private CartService $cart,
    ) {}

    #[Get('/collections')]
    public function index(): Response
    {
        $products = $this->products->findAll();

        return $this->view->render('shop::pages/collections', [
            'products' => $products,
            'cartCount' => $this->cart->getCount(),
        ]);
    }
}
