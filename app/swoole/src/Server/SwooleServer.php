<?php

declare(strict_types=1);

namespace App\Swoole\Server;

use Marko\Core\Application;
use Marko\Routing\Http\Request;
use Marko\Routing\Http\Response;
use Marko\Session\Contracts\SessionInterface;
use Marko\Session\Session;
use OpenSwoole\HTTP\Request as SwooleRequest;
use OpenSwoole\HTTP\Response as SwooleResponse;
use OpenSwoole\HTTP\Server;
use ReflectionProperty;

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
        // Set superglobals per-request so session_start() reads the right cookie
        $_COOKIE = $swooleRequest->cookie ?? [];
        $_SERVER = $this->buildServerArray($swooleRequest);

        // Reset session singleton so it calls session_start() fresh per request
        $this->resetSession();

        $request = $this->convertRequest($swooleRequest);
        $response = $this->app->router->handle($request);

        // Set session cookie manually — PHP's native header() doesn't work in Swoole
        $this->setSessionCookie($swooleResponse);

        $this->sendResponse($response, $swooleResponse);

        // Clean up to avoid leaking state between requests
        $_COOKIE = [];
        $_SERVER = [];
    }

    private function setSessionCookie(SwooleResponse $swooleResponse): void
    {
        $sessionId = session_id();
        if ($sessionId === '' || $sessionId === false) {
            return;
        }

        $params = session_get_cookie_params();
        $swooleResponse->cookie(
            session_name(),
            $sessionId,
            $params['lifetime'] > 0 ? time() + $params['lifetime'] : 0,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly'],
            $params['samesite'] ?? '',
        );
    }

    private function resetSession(): void
    {
        $session = $this->app->container->get(SessionInterface::class);

        if (!$session instanceof Session) {
            return;
        }

        // Force-close any active PHP session — session_write_close() alone
        // doesn't fully reset session state in OpenSwoole's persistent process
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_unset();
        $_SESSION = [];
        session_id('');

        // Session is a singleton — reset its internal state via Reflection
        $started = new ReflectionProperty(Session::class, 'started');
        $started->setValue($session, false);

        $data = new ReflectionProperty(Session::class, 'data');
        $data->setValue($session, []);

        // Set Session::$id from the incoming cookie so Session::start()
        // calls session_id($id) before session_start()
        $cookieName = session_name() ?: 'marko_session';
        $incomingId = $_COOKIE[$cookieName] ?? '';

        $id = new ReflectionProperty(Session::class, 'id');
        $id->setValue($session, $incomingId);
    }

    private function buildServerArray(SwooleRequest $swooleRequest): array
    {
        $server = [];
        foreach (($swooleRequest->server ?? []) as $key => $value) {
            $server[strtoupper($key)] = $value;
        }
        foreach (($swooleRequest->header ?? []) as $key => $value) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', $key))] = $value;
        }

        $server['REQUEST_METHOD'] = strtoupper($server['REQUEST_METHOD'] ?? 'GET');
        $server['REQUEST_URI'] = $server['REQUEST_URI'] ?? '/';
        if (isset($server['HTTP_CONTENT_TYPE'])) {
            $server['CONTENT_TYPE'] = $server['HTTP_CONTENT_TYPE'];
        }

        return $server;
    }

    private function convertRequest(SwooleRequest $swooleRequest): Request
    {
        $server = $_SERVER;

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
