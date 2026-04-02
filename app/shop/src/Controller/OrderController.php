<?php

declare(strict_types=1);

namespace App\Shop\Controller;

use App\Shop\Repository\OrderRepository;
use App\Shop\Repository\OrderItemRepository;
use App\Shop\Service\CartService;
use Marko\Routing\Attributes\Get;
use Marko\Routing\Http\Response;
use Marko\Session\Contracts\SessionInterface;
use Marko\View\ViewInterface;

class OrderController
{
    public function __construct(
        private ViewInterface $view,
        private OrderRepository $orders,
        private OrderItemRepository $orderItems,
        private CartService $cart,
        private SessionInterface $session,
    ) {}

    #[Get('/order/{reference}')]
    public function show(string $reference): Response
    {
        $reference = urldecode($reference);
        $order = $this->orders->findByReference($reference);

        if ($order === null) {
            return new Response('Order not found', 404);
        }

        // Verify the order belongs to the current session
        $sessionOrders = $this->session->get('completed_orders', []);
        if (!in_array($order->reference, $sessionOrders, true)) {
            return new Response('Order not found', 404);
        }

        $items = $this->orderItems->findByOrderId($order->id);

        return $this->view->render('shop::pages/order-complete', [
            'order' => $order,
            'items' => $items,
            'cartCount' => $this->cart->getCount(),
        ]);
    }
}
