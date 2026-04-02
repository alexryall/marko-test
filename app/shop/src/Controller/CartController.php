<?php

declare(strict_types=1);

namespace App\Shop\Controller;

use App\Shop\Repository\ProductRepository;
use App\Shop\Service\CartService;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Attributes\Post;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;

class CartController
{
    public function __construct(
        private CartService $cart,
        private ProductRepository $products,
    ) {}

    #[Get('/cart')]
    public function index(): Response
    {
        return Response::json([
            'items' => array_values($this->cart->getItems()),
            'count' => $this->cart->getCount(),
            'subtotal' => $this->cart->getSubtotal(),
            'formattedSubtotal' => $this->cart->formattedSubtotal(),
        ]);
    }

    #[Post('/cart/add')]
    public function add(Request $request): Response
    {
        $productId = (int) $request->post('product_id');
        $size = $request->post('size', 'One Size');
        $color = $request->post('color', 'Default');

        $product = $this->products->find($productId);

        if ($product === null) {
            return Response::json(['error' => 'Product not found'], 404);
        }

        $this->cart->addItem(
            productId: $product->id,
            name: $product->name,
            price: $product->price,
            size: $size,
            color: $color,
            imageUrl: $product->image_url,
        );

        return Response::json([
            'items' => array_values($this->cart->getItems()),
            'count' => $this->cart->getCount(),
            'subtotal' => $this->cart->getSubtotal(),
            'formattedSubtotal' => $this->cart->formattedSubtotal(),
        ]);
    }

    #[Post('/cart/update')]
    public function update(Request $request): Response
    {
        $key = $request->post('key', '');
        $quantity = (int) $request->post('quantity', '0');

        $this->cart->updateQuantity($key, $quantity);

        return Response::json([
            'items' => array_values($this->cart->getItems()),
            'count' => $this->cart->getCount(),
            'subtotal' => $this->cart->getSubtotal(),
            'formattedSubtotal' => $this->cart->formattedSubtotal(),
        ]);
    }

    #[Post('/cart/remove')]
    public function remove(Request $request): Response
    {
        $key = $request->post('key', '');
        $this->cart->removeItem($key);

        return Response::json([
            'items' => array_values($this->cart->getItems()),
            'count' => $this->cart->getCount(),
            'subtotal' => $this->cart->getSubtotal(),
            'formattedSubtotal' => $this->cart->formattedSubtotal(),
        ]);
    }
}
