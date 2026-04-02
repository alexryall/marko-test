<?php

declare(strict_types=1);

namespace App\Hello\Controller;

use Marko\Routing\Attributes\Get;
use Marko\Routing\Http\Response;

class HelloController
{
    #[Get('/')]
    public function index(): Response
    {
        return new Response('Hello from Marko!');
    }
}
