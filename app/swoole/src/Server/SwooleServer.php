<?php

declare(strict_types=1);

namespace App\Swoole\Server;

use Marko\Core\Application;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use OpenSwoole\HTTP\Request as SwooleRequest;
use OpenSwoole\HTTP\Response as SwooleResponse;
use OpenSwoole\HTTP\Server;

class SwooleServer
{
    private Server $server;

    private Application $app;

    public function __construct(
        private readonly string $basePath,
        private readonly string $host = '0.0.0.0',
        private readonly int $port = 8000,
        private readonly int $workers = 4,
    ) {}

    public function start(): void
    {
        $this->app = Application::boot($this->basePath);

        $this->server = new Server($this->host, $this->port);
        $this->server->set([
            'worker_num' => $this->workers,
            'document_root' => $this->basePath . '/public',
            'enable_static_handler' => true,
        ]);

        $this->server->on('request', function (SwooleRequest $swooleRequest, SwooleResponse $swooleResponse): void {
            $this->handleRequest($swooleRequest, $swooleResponse);
        });

        $this->server->on('start', function () {
            echo "Swoole server listening on http://{$this->host}:{$this->port}\n";
            echo "Workers: {$this->workers}\n";

            // Write PID file for the down command
            $pidFile = $this->basePath . '/.marko/swoole.pid';
            $dir = dirname($pidFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($pidFile, (string) getmypid());
        });

        $this->server->on('shutdown', function () {
            $pidFile = $this->basePath . '/.marko/swoole.pid';
            if (file_exists($pidFile)) {
                unlink($pidFile);
            }
        });

        $this->server->start();
    }

    private function handleRequest(SwooleRequest $swooleRequest, SwooleResponse $swooleResponse): void
    {
        $request = $this->convertRequest($swooleRequest);
        $response = $this->app->router->handle($request);
        $this->sendResponse($response, $swooleResponse);
    }

    private function convertRequest(SwooleRequest $swooleRequest): Request
    {
        $server = [];
        foreach (($swooleRequest->server ?? []) as $key => $value) {
            $server[strtoupper($key)] = $value;
        }
        foreach (($swooleRequest->header ?? []) as $key => $value) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
        }

        // Map Swoole server keys to PHP superglobal format
        $server['REQUEST_METHOD'] = strtoupper($server['REQUEST_METHOD'] ?? 'GET');
        $server['REQUEST_URI'] = $server['REQUEST_URI'] ?? '/';
        if (isset($server['HTTP_CONTENT_TYPE'])) {
            $server['CONTENT_TYPE'] = $server['HTTP_CONTENT_TYPE'];
        }

        $query = $swooleRequest->get ?? [];
        $post = $swooleRequest->post ?? [];
        $body = $swooleRequest->rawContent() ?: '';

        // Parse body for PUT/PATCH/DELETE like Request::fromGlobals does
        $method = $server['REQUEST_METHOD'];
        if ($post === [] && $body !== '' && in_array($method, ['PUT', 'PATCH', 'DELETE'], true)) {
            $contentType = $server['CONTENT_TYPE'] ?? '';
            if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
                parse_str($body, $post);
            }
        }

        return new Request(
            server: $server,
            query: $query,
            post: $post,
            body: $body,
        );
    }

    private function sendResponse(Response $response, SwooleResponse $swooleResponse): void
    {
        $swooleResponse->status($response->statusCode());

        foreach ($response->headers() as $name => $value) {
            $swooleResponse->header($name, $value);
        }

        $swooleResponse->end($response->body());
    }
}
