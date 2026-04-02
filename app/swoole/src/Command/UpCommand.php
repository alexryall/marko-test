<?php

declare(strict_types=1);

namespace App\Swoole\Command;

use Marko\Core\Attributes\Command;
use Marko\Core\Command\CommandInterface;
use Marko\Core\Command\Input;
use Marko\Core\Command\Output;
use Marko\Core\Path\ProjectPaths;

#[Command(name: 'swoole:up', description: 'Start the OpenSwoole HTTP server', aliases: ['up'])]
class UpCommand implements CommandInterface
{
    public function __construct(
        private ProjectPaths $paths,
    ) {}

    public function execute(Input $input, Output $output): int
    {
        $port = (int) ($input->getOption('port') ?? $input->getOption('p') ?? 8000);
        $workers = (int) ($input->getOption('workers') ?? $input->getOption('w') ?? 4);
        $detach = $input->hasOption('detach') || $input->hasOption('d');

        $pidFile = $this->paths->base . '/.marko/swoole.pid';

        if (file_exists($pidFile)) {
            $pid = (int) file_get_contents($pidFile);
            if ($pid > 0 && function_exists('posix_kill') && posix_kill($pid, 0)) {
                $output->writeLine("Swoole server is already running (PID $pid).");
                $output->writeLine("Run 'marko down' to stop it first.");

                return 1;
            }

            // Stale PID file
            unlink($pidFile);
        }

        if (!extension_loaded('openswoole')) {
            $output->writeLine('Error: OpenSwoole extension is not installed.');
            $output->writeLine('Install it with: pecl install openswoole');

            return 1;
        }

        $serverScript = $this->paths->base . '/bin/swoole-server.php';

        if (!file_exists($serverScript)) {
            $output->writeLine("Error: bin/swoole-server.php not found.");

            return 1;
        }

        $command = sprintf(
            'php %s --port=%d --workers=%d',
            escapeshellarg($serverScript),
            $port,
            $workers,
        );

        if ($detach) {
            $output->writeLine('Starting Swoole server (detached)...');
            $pid = $this->startDetached($command);

            if ($pid === null) {
                $output->writeLine('Error: failed to start Swoole server.');

                return 1;
            }

            // Wait briefly and verify
            usleep(300000);
            if (!posix_kill($pid, 0)) {
                $output->writeLine('Error: Swoole server failed to start. Port may be in use.');

                return 1;
            }

            $output->writeLine("Swoole server started on http://localhost:$port (PID $pid)");
            $output->writeLine("Workers: $workers");
            $output->writeLine("Run 'marko down' to stop.");
        } else {
            $output->writeLine("Starting Swoole server on http://localhost:$port...");
            $output->writeLine("Workers: $workers");
            $output->writeLine('Press Ctrl+C to stop.');
            $output->writeLine('');

            $this->runForeground($command);
        }

        return 0;
    }

    private function startDetached(string $command): ?int
    {
        $pidStr = shell_exec("nohup $command > /dev/null 2>&1 & echo \$!");

        if ($pidStr === null) {
            return null;
        }

        $pid = (int) trim($pidStr);

        return $pid > 0 ? $pid : null;
    }

    private function runForeground(string $command): void
    {
        $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes);

        if (is_resource($process)) {
            proc_close($process);
        }
    }
}
