<?php

namespace Database\Seeders;

use Cachet\Enums\ComponentStatusEnum;
use Cachet\Models\Component;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeds the hrConnectum services that the status page tracks, from
 * config/hrconnectum.php ('components'). The same list drives the `status:check`
 * health-check command, so there is a single source of truth.
 *
 * Run once after the first deploy:
 *
 *     php artisan db:seed --class=ComponentsSeeder --force
 *
 * Idempotent and status-preserving: components are matched by name, so a re-run
 * updates metadata (description, link, order) in place without duplicating, and
 * never resets a component's live status. To change what is tracked, edit the
 * 'components' list in config/hrconnectum.php and re-run.
 */
class ComponentsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach (config('hrconnectum.components', []) as $order => $definition) {
            $component = Component::firstOrNew(['name' => $definition['name']]);

            $component->fill([
                'description' => $definition['description'] ?? null,
                'link' => $definition['url'] ?? null,
                'component_group_id' => null,
                'order' => $order,
                'enabled' => true,
            ]);

            // Set the starting status only on first creation, so re-running this
            // seeder never overwrites a live status set by the monitor or an operator.
            if (! $component->exists) {
                $component->status = ComponentStatusEnum::operational;
            }

            $component->save();
        }
    }
}
