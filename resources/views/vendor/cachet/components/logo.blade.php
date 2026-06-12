{{--
    hrConnectum logo override (additive layer; replaces cachet::logo).

    A single <img> carries the caller's sizing/visibility classes ($attributes),
    so the logo renders at the correct, consistent size everywhere it is used
    (header h-8, footer h-4, setup h-10) regardless of the PNG's intrinsic size.
    <picture> swaps to the white wordmark when the browser prefers a dark colour
    scheme, which is exactly how Cachet toggles dark mode (prefers-color-scheme).
--}}
<picture>
    <source srcset="{{ asset('vendor/hrconnectum/hr_logo_white.png') }}" media="(prefers-color-scheme: dark)" />
    <img src="{{ asset('vendor/hrconnectum/hr_logo_black.png') }}" alt="hrConnectum" {{ $attributes }} />
</picture>
