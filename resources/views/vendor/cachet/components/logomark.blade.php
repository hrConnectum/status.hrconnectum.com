{{--
    hrConnectum logomark override (additive layer; replaces cachet::logomark).
    Shown on small screens. Same brand wordmark and the same single-<img>
    approach as the logo component, so the caller's height class applies
    directly and the dark-mode variant swaps via prefers-color-scheme.
--}}
<picture>
    <source srcset="{{ asset('vendor/hrconnectum/hr_logo_white.png') }}" media="(prefers-color-scheme: dark)" />
    <img src="{{ asset('vendor/hrconnectum/hr_logo_black.png') }}" alt="hrConnectum" {{ $attributes }} />
</picture>
