<?php

namespace Database\Seeders;

use Cachet\Enums\ComponentGroupVisibilityEnum;
use Cachet\Enums\ComponentStatusEnum;
use Cachet\Enums\ResourceVisibilityEnum;
use Cachet\Models\Component;
use Cachet\Models\ComponentGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Seeds the hrConnectum service catalogue (component groups + components) that
 * the status page tracks.
 *
 * Run once after the first deploy (it is NOT part of the recurring deploy
 * script, so it never fights statuses set by operators):
 *
 *     php artisan db:seed --class=ComponentsSeeder --force
 *
 * Idempotent and safe to re-run: groups and components are matched by name, so
 * re-running updates metadata (description, order, grouping) in place without
 * duplicating. A component's live status is only set on first creation, so a
 * re-run will never reset an active outage back to "operational".
 *
 * To change what is tracked, edit the $catalogue tree below and re-run.
 */
class ComponentsSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * group name => [ collapsed behaviour, components[] ]
     *
     * @var array<int, array{name: string, collapsed: ComponentGroupVisibilityEnum, components: array<int, array{name: string, description?: string, link?: string}>}>
     */
    private array $catalogue = [
        [
            'name' => 'Core Platform',
            'collapsed' => ComponentGroupVisibilityEnum::expanded,
            'components' => [
                ['name' => 'Web Application', 'description' => 'The main hrConnectum web application.', 'link' => 'https://hrconnectum.com'],
                ['name' => 'Recruiter Dashboard', 'description' => 'Recruiter and hiring-manager dashboard.'],
                ['name' => 'REST API', 'description' => 'Public and internal API endpoints.'],
                ['name' => 'Authentication & SSO', 'description' => 'Sign-in, sessions and single sign-on.'],
            ],
        ],
        [
            'name' => 'Candidate Sourcing',
            'collapsed' => ComponentGroupVisibilityEnum::expanded,
            'components' => [
                ['name' => 'LinkedIn Integration', 'description' => 'Candidate sourcing from LinkedIn.'],
                ['name' => 'Xing Integration', 'description' => 'Candidate sourcing from Xing.'],
                ['name' => 'Stack Overflow Integration', 'description' => 'Developer sourcing from Stack Overflow.'],
                ['name' => 'Candidate Search', 'description' => 'Search, matching and ranking engine.'],
            ],
        ],
        [
            'name' => 'Infrastructure',
            'collapsed' => ComponentGroupVisibilityEnum::collapsed_unless_incident,
            'components' => [
                ['name' => 'Database', 'description' => 'Primary MySQL database.'],
                ['name' => 'File Storage', 'description' => 'Resume and document storage.'],
                ['name' => 'Background Jobs', 'description' => 'Queue workers and scheduled tasks.'],
                ['name' => 'Email Delivery', 'description' => 'Transactional and notification email.'],
            ],
        ],
    ];

    public function run(): void
    {
        foreach ($this->catalogue as $groupOrder => $groupData) {
            $group = ComponentGroup::updateOrCreate(
                ['name' => $groupData['name']],
                [
                    'order' => $groupOrder,
                    'collapsed' => $groupData['collapsed'],
                    'visible' => ResourceVisibilityEnum::guest,
                ],
            );

            foreach ($groupData['components'] as $componentOrder => $componentData) {
                $component = Component::firstOrNew(['name' => $componentData['name']]);

                $component->fill([
                    'description' => $componentData['description'] ?? null,
                    'link' => $componentData['link'] ?? null,
                    'component_group_id' => $group->id,
                    'order' => $componentOrder,
                    'enabled' => true,
                ]);

                // Set the starting status only when the component is first created,
                // so re-running this seeder never overwrites a live status.
                if (! $component->exists) {
                    $component->status = ComponentStatusEnum::operational;
                }

                $component->save();
            }
        }
    }
}
