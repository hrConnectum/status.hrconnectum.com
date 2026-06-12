<?php

/*
|--------------------------------------------------------------------------
| hrConnectum branding & monitoring (additive layer)
|--------------------------------------------------------------------------
|
| Custom configuration owned by hrConnectum. Nothing here lives inside the
| Cachet core package, so it survives `composer update`.
|
| - accent / palettes : brand colour, applied by BrandingSeeder.
| - components         : the services shown on the status page. Seeded by
|                        ComponentsSeeder and health-checked every minute by
|                        the `status:check` command.
| - monitor            : settings for the `status:check` command.
|
*/

return [

    // Active brand palette: one of the keys under "palettes" below.
    'accent' => env('HRC_ACCENT', 'indigo'),

    'palettes' => [

        'teal' => [
            'label' => 'Teal / Sky',
            'theme' => 'teal',
            'accent'          => '#377D8E', // brand teal 700
            'foreground'      => '#FFFFFF',
            'background'      => '#E5FCF5', // brand teal 100
            'accent_dark'     => '#6FC4C6', // brand teal 500
            'foreground_dark' => '#0B2B33',
            'background_dark' => '#15445F', // brand teal 900
        ],

        'indigo' => [
            'label' => 'Indigo Navy',
            'theme' => 'indigo',
            'accent'          => '#212154', // brand indigo 500
            'foreground'      => '#FFFFFF',
            'background'      => '#ECECF6',
            'accent_dark'     => '#8181CB', // brand indigo 300
            'foreground_dark' => '#0C0C24',
            'background_dark' => '#181848', // brand indigo 600
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Tracked services
    |--------------------------------------------------------------------------
    |
    | Each entry becomes a public Cachet component (via ComponentsSeeder) and is
    | health-checked every minute by `status:check`. "url" is the public page
    | that gets an HTTP request. Only list things that have a real public URL we
    | can reach from the outside; everything else belongs to manual incidents.
    |
    */
    'components' => [
        [
            'name' => 'hrConnectum Tool',
            'description' => 'The hrConnectum recruitment application.',
            'url' => env('HRC_TOOL_URL', 'https://tool.hrconnectum.com'),
        ],
        [
            'name' => 'hrConnectum Website',
            'description' => 'The hrConnectum marketing website.',
            'url' => env('HRC_WEBSITE_URL', 'https://hrconnectum.com'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitor settings (status:check)
    |--------------------------------------------------------------------------
    */
    'monitor' => [
        // Seconds to wait for each request before treating it as a failure.
        'timeout' => (int) env('HRC_MONITOR_TIMEOUT', 10),

        // Consecutive failures required before a component is marked down.
        // Recovery to "operational" happens on the first successful check.
        // Raise this to avoid false alarms from brief network blips.
        'failure_threshold' => (int) env('HRC_MONITOR_FAILURE_THRESHOLD', 2),

        // Status to apply when a component is considered down:
        // 'major_outage' | 'partial_outage' | 'performance_issues'.
        'down_status' => env('HRC_MONITOR_DOWN_STATUS', 'major_outage'),
    ],

];
