<?php

namespace App\Console\Commands;

use Cachet\Enums\ComponentStatusEnum;
use Cachet\Models\Component;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Health-checks the hrConnectum services listed in config/hrconnectum.php and
 * updates their Cachet components, so the status page reflects reality instead
 * of a value someone set by hand.
 *
 * For each service it makes one HTTP request to the configured URL:
 *   - any response below HTTP 500 (incl. redirects, auth/bot-block pages) counts
 *     as "up", because the server answered;
 *   - a 5xx response, a timeout, or a connection error counts as "down".
 *
 * A component is only flipped to "down" after `failure_threshold` consecutive
 * failures (tracked in the component's meta), and recovers to "operational" on
 * the first success. Scheduled every minute in routes/console.php.
 */
class CheckComponents extends Command
{
    protected $signature = 'status:check';

    protected $description = 'Health-check the hrConnectum services and update their status page components.';

    public function handle(): int
    {
        $timeout = (int) config('hrconnectum.monitor.timeout', 10);
        $threshold = max(1, (int) config('hrconnectum.monitor.failure_threshold', 2));
        $downStatus = $this->downStatus();

        foreach (config('hrconnectum.components', []) as $definition) {
            $url = $definition['url'] ?? null;
            $component = Component::query()->where('name', $definition['name'])->first();

            if ($component === null || empty($url)) {
                continue;
            }

            $this->evaluate($component, $url, $timeout, $threshold, $downStatus);
        }

        return self::SUCCESS;
    }

    private function evaluate(Component $component, string $url, int $timeout, int $threshold, ComponentStatusEnum $downStatus): void
    {
        $isUp = $this->isReachable($url, $timeout);

        $meta = $component->meta ?? [];
        $failures = $isUp ? 0 : ((int) ($meta['check']['failures'] ?? 0)) + 1;

        // Stay on the current status until we have failed enough times in a row.
        $target = $isUp
            ? ComponentStatusEnum::operational
            : ($failures >= $threshold ? $downStatus : ($component->status ?? ComponentStatusEnum::operational));

        $meta['check'] = [
            'url' => $url,
            'last_ok' => $isUp,
            'failures' => $failures,
            'checked_at' => now()->toIso8601String(),
        ];
        $component->meta = $meta;

        if ($component->status !== $target) {
            $this->warn(sprintf(
                '%s: %s -> %s',
                $component->name,
                $component->status?->name ?? 'unknown',
                $target->name,
            ));
            $component->status = $target;
        } else {
            $this->line(sprintf('%s: %s', $component->name, $target->name));
        }

        $component->save();
    }

    private function isReachable(string $url, int $timeout): bool
    {
        try {
            $response = Http::timeout($timeout)
                ->withHeaders(['User-Agent' => 'hrConnectum-StatusBot/1.0 (+https://status.hrconnectum.com)'])
                ->get($url);

            return $response->status() < 500;
        } catch (Throwable) {
            return false;
        }
    }

    private function downStatus(): ComponentStatusEnum
    {
        return match (config('hrconnectum.monitor.down_status', 'major_outage')) {
            'performance_issues' => ComponentStatusEnum::performance_issues,
            'partial_outage' => ComponentStatusEnum::partial_outage,
            default => ComponentStatusEnum::major_outage,
        };
    }
}
