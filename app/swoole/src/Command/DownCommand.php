<?php

declare(strict_types=1);

namespace App\Swoole\Command;

use Marko\Core\Attributes\Command;
use Marko\Core\Command\CommandInterface;
use Marko\Core\Command\Input;
use Marko\Core\Command\Output;
use Marko\Core\Path\ProjectPaths;

#[Command(name: 'swoole:down', description: 'Stop the OpenSwoole HTTP server', aliases: ['down'])]
class DownCommand implements CommandInterface
{
    public function __construct(
        private ProjectPaths $paths,
    ) {}

    public function execute(Input $input, Output $output): int
    {
        $pidFile = $this->paths->base . '/.marko/swoole.pid';

        if (!file_exists($pidFile)) {
            $output->writeLine('No Swoole server running.');

            return 0;
        }

        $pid = (int) file_get_contents($pidFile);

        if ($pid <= 0) {
            unlink($pidFile);
            $output->writeLine('No Swoole server running (stale PID file removed).');

            return 0;
        }

        if (!function_exists('posix_kill') || !posix_kill($pid, 0)) {
            unlink($pidFile);
            $output->writeLine('Swoole server already stopped (stale PID file removed).');

            return 0;
        }

        $output->writeLine("Stopping Swoole server (PID $pid)...");

        // SIGTERM to the master process — Swoole handles graceful shutdown of workers
        posix_kill($pid, 15);

        // Wait up to 5 seconds for shutdown
        for ($i = 0; $i < 50; $i++) {
            usleep(100000);
            if (!posix_kill($pid, 0)) {
                break;
            }
        }

        // Force kill if still running
        if (posix_kill($pid, 0)) {
            posix_kill($pid, 9);
            usleep(100000);
        }

        if (file_exists($pidFile)) {
            unlink($pidFile);
        }

        $output->writeLine('Swoole server stopped.');

        return 0;
    }
}
