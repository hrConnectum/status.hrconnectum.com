<?php

/*
|--------------------------------------------------------------------------
| hrConnectum branding (additive layer)
|--------------------------------------------------------------------------
|
| Custom configuration owned by hrConnectum. Nothing here lives inside the
| Cachet core package, so it survives `composer update`. The BrandingSeeder
| reads these values and writes them into Cachet's settings (theme accent,
| custom stylesheet, etc.). To switch the brand colour, change HRC_ACCENT in
| .env (or the default below) and re-run: php artisan db:seed --class=BrandingSeeder
|
*/

return [

    // Active brand palette: one of the keys under "palettes" below.
    'accent' => env('HRC_ACCENT', 'indigo'),

    'palettes' => [

        'teal' => [
            'label' => 'Teal / Sky',
            // Nearest Filament named theme used as the Cachet accent base.
            'theme' => 'teal',
            // Light mode (exact hrConnectum brand hex, injected via custom CSS).
            'accent'          => '#377D8E', // brand teal 700
            'foreground'      => '#FFFFFF',
            'background'      => '#E5FCF5', // brand teal 100
            // Dark mode.
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

];
