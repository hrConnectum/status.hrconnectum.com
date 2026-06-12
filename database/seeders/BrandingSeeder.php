<?php

namespace Database\Seeders;

use Cachet\Settings\AppSettings;
use Cachet\Settings\CustomizationSettings;
use Cachet\Settings\ThemeSettings;
use Illuminate\Database\Seeder;
use InvalidArgumentException;

/**
 * Applies hrConnectum branding to Cachet's stored settings.
 *
 * This is the additive, repeatable way to brand the status page: it writes
 * values into Cachet's own settings tables (theme, app, customization) and
 * never edits the core package. Re-run any time, including on deploy:
 *
 *     php artisan db:seed --class=BrandingSeeder --force
 *
 * The brand colour is driven by config/hrconnectum.php (env: HRC_ACCENT).
 */
class BrandingSeeder extends Seeder
{
    public function run(): void
    {
        $key = (string) config('hrconnectum.accent', 'teal');
        $brand = config("hrconnectum.palettes.{$key}");

        if (! is_array($brand)) {
            throw new InvalidArgumentException("Unknown hrConnectum palette [{$key}]. Check config/hrconnectum.php.");
        }

        $this->applyAppSettings();
        $this->applyThemeSettings($brand);
        $this->applyCustomizationSettings($brand);
    }

    private function applyAppSettings(): void
    {
        $settings = app(AppSettings::class);
        $settings->name = 'hrConnectum';
        $settings->about = 'Welcome to the hrConnectum status page. Here you can monitor the real-time '
            .'availability and performance of hrConnectum services and review the history of past incidents.';
        $settings->timezone = 'Europe/Istanbul';
        $settings->locale = 'en';
        $settings->show_support = true;
        $settings->save();
    }

    /**
     * @param  array<string, string>  $brand
     */
    private function applyThemeSettings(array $brand): void
    {
        $settings = app(ThemeSettings::class);
        $settings->accent = $brand['theme'];
        $settings->accent_pairing = true;
        $settings->app_banner = null; // logo handled by the published logo component override
        $settings->save();
    }

    /**
     * @param  array<string, string>  $brand
     */
    private function applyCustomizationSettings(array $brand): void
    {
        $settings = app(CustomizationSettings::class);
        $settings->stylesheet = $this->stylesheet($brand);
        $settings->header = $this->header();
        $settings->footer = $this->footer();
        $settings->save();
    }

    /**
     * Custom CSS injected after Cachet's generated theme styles, so these
     * variables win. This is where the exact hrConnectum brand hex lives.
     *
     * @param  array<string, string>  $b
     */
    private function stylesheet(array $b): string
    {
        return <<<CSS
        /* hrConnectum brand accent — overrides Cachet's named theme with exact brand colours. */
        :root {
            --accent: {$b['accent']};
            --accent-content: {$b['accent']};
            --accent-foreground: {$b['foreground']};
            --accent-background: {$b['background']};
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --accent: {$b['accent_dark']};
                --accent-content: {$b['accent_dark']};
                --accent-foreground: {$b['foreground_dark']};
                --accent-background: {$b['background_dark']};
            }
        }
        CSS;
    }

    private function header(): string
    {
        return '<link rel="icon" type="image/png" href="/vendor/hrconnectum/hrc_logo_sky.png">';
    }

    private function footer(): string
    {
        return <<<'HTML'
        <footer class="border-t border-zinc-900/10 dark:border-white/10">
            <div class="container mx-auto flex max-w-5xl flex-col items-center justify-between gap-2 px-4 py-6 text-sm text-zinc-500 sm:flex-row sm:px-6 lg:px-8 dark:text-zinc-400">
                <span>&copy; 2026 hrConnectum. All rights reserved.</span>
                <span class="flex items-center gap-4">
                    <a href="https://hrconnectum.com" class="transition hover:text-accent">Website</a>
                    <a href="mailto:support@hrconnectum.com" class="transition hover:text-accent">Support</a>
                </span>
            </div>
        </footer>
        HTML;
    }
}
