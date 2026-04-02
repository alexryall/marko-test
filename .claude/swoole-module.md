# Swoole Module

OpenSwoole-based HTTP server that replaces the default PHP built-in dev server. The app boots once and handles all requests from memory.

## Module: `app/swoole`

Namespace: `App\Swoole`

### Prerequisites

- **OpenSwoole extension** (`pecl install openswoole`)
- Built from source on PHP 8.5 with pcre2: `CPPFLAGS="-I$(brew --prefix pcre2)/include" make`
- Enabled via `/opt/homebrew/etc/php/8.5/conf.d/ext-openswoole.ini`

### What Changed

- Removed `marko/dev-server` (PHP built-in server) from dev dependencies
- Created `app/swoole/` module that registers `up` and `down` command aliases

### Directory Structure

```
app/swoole/
├── composer.json
└── src/
    ├── Command/
    │   ├── UpCommand.php       # marko up (alias: up)
    │   └── DownCommand.php     # marko down (alias: down)
    └── Server/
        SwooleServer.php        # OpenSwoole HTTP server wrapper

bin/
└── swoole-server.php           # Entry point script spawned by UpCommand
```

### Commands

| Command | Aliases | Description |
|---------|---------|-------------|
| `swoole:up` | `up` | Start the OpenSwoole server |
| `swoole:down` | `down` | Stop the OpenSwoole server |

### Options (`marko up`)

| Option | Short | Default | Description |
|--------|-------|---------|-------------|
| `--port` | `-p` | 8000 | HTTP port |
| `--workers` | `-w` | 4 | Number of worker processes |
| `--detach` | `-d` | — | Run in background |
| `--foreground` | `-f` | default | Run in foreground (Ctrl+C to stop) |

### How It Works

1. `UpCommand` spawns `bin/swoole-server.php` as a child process (foreground or detached)
2. `bin/swoole-server.php` registers the `App\Swoole` autoloader (app modules aren't in Composer's autoload) then creates a `SwooleServer`
3. `SwooleServer::start()` calls `Application::boot()` once, then starts an OpenSwoole HTTP server
4. On each request, `SwooleServer::handleRequest()` converts the OpenSwoole request into a Marko `Request` (mapping headers, query, post, body to the same format as `$_SERVER`/`$_GET`/`$_POST`) and passes it to `$app->router->handle()`
5. The Marko `Response` is written back to the OpenSwoole response object

### PID Management

- Master PID written to `.marko/swoole.pid` on server start
- `DownCommand` reads PID file, sends SIGTERM for graceful shutdown, waits up to 5 seconds, then SIGKILL if needed
- `UpCommand` checks for stale PID files and cleans them up

### Static Files

OpenSwoole serves static files from `public/` directly via `enable_static_handler` — no need for PHP to handle CSS/JS/images.

### Session Handling in Swoole

PHP's native session system doesn't work correctly in a persistent process — singletons retain state, `session_write_close()` doesn't fully reset, and `header()` doesn't reach clients. `SwooleServer` handles this by:

1. Setting `$_COOKIE`, `$_SERVER`, `$_SESSION` per-request from the Swoole request
2. Calling `session_destroy()` between requests (unlike `session_write_close()`, this fully resets PHP's session state in OpenSwoole)
3. Resetting the `Session` singleton's internal state (`started`, `id`, `data`) via Reflection
4. Setting `Session::$id` from the incoming cookie so `Session::start()` resumes the correct session
5. Manually sending the `Set-Cookie` header via `$swooleResponse->cookie()` (PHP's `header()` is invisible to Swoole)

### Key Difference from PHP Built-in Server

The PHP built-in server (`php -S`) re-executes `public/index.php` on every request, meaning `Application::boot()` runs each time. With OpenSwoole, the application boots once and stays in memory — route discovery, module loading, container setup, and database connections are all reused across requests.
