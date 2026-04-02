<?php

declare(strict_types=1);

namespace App\Shop\Controller;

use App\Shop\Entity\Order;
use App\Shop\Entity\OrderItem;
use App\Shop\Repository\OrderRepository;
use App\Shop\Repository\OrderItemRepository;
use App\Shop\Service\CartService;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Attributes\Post;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\View\ViewInterface;

class CheckoutController
{
    public function __construct(
        private ViewInterface $view,
        private CartService $cart,
        private OrderRepository $orders,
        private OrderItemRepository $orderItems,
    ) {}

    #[Get('/checkout')]
    public function index(): Response
    {
        $items = $this->cart->getItems();

        if (empty($items)) {
            return Response::redirect('/collections');
        }

        return $this->view->render('shop::pages/checkout', [
            'items' => array_values($items),
            'subtotal' => $this->cart->getSubtotal(),
            'formattedSubtotal' => $this->cart->formattedSubtotal(),
            'cartCount' => $this->cart->getCount(),
        ]);
    }

    #[Post('/checkout/place-order')]
    public function placeOrder(Request $request): Response
    {
        $items = $this->cart->getItems();

        if (empty($items)) {
            return Response::redirect('/collections');
        }

        $email = $request->post('email', '');
        $firstName = $request->post('first_name', '');
        $lastName = $request->post('last_name', '');
        $shippingMethod = $request->post('shipping_method', 'standard');

        $shippingCost = $shippingMethod === 'express' ? 2500 : 0;
        $subtotal = $this->cart->getSubtotal();
        $tax = (int) round($subtotal * 0.085);
        $total = $subtotal + $shippingCost + $tax;

        $reference = '#MS-' . random_int(100000, 999999);

        $order = new Order();
        $order->reference = $reference;
        $order->email = $email;
        $order->first_name = $firstName;
        $order->last_name = $lastName;
        $order->shipping_method = $shippingMethod === 'express' ? 'Express Atelier Shipping' : 'Standard Delivery';
        $order->shipping_cost = $shippingCost;
        $order->subtotal = $subtotal;
        $order->tax = $tax;
        $order->total = $total;
        $order->created_at = date('Y-m-d H:i:s');

        $this->orders->save($order);

        foreach ($items as $item) {
            $orderItem = new OrderItem();
            $orderItem->order_id = $order->id;
            $orderItem->product_id = $item['product_id'];
            $orderItem->product_name = $item['name'];
            $orderItem->price = $item['price'];
            $orderItem->quantity = $item['quantity'];
            $orderItem->size = $item['size'];
            $orderItem->color = $item['color'];

            $this->orderItems->save($orderItem);
        }

        $this->cart->clear();

        return Response::redirect('/order/' . $order->id);
    }
}
