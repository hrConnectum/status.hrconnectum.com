{{--
    hrConnectum logo override (additive layer).
    Replaces the default cachet::logo component without touching the core package.
    Light/dark variants swap automatically; sizing/visibility classes are inherited
    from the caller (e.g. "hidden h-8 w-auto sm:block").
--}}
<span {{ $attributes }}>
    <img src="{{ asset('vendor/hrconnectum/hr_logo_black.png') }}" alt="hrConnectum" class="h-full w-auto dark:hidden" />
    <img src="{{ asset('vendor/hrconnectum/hr_logo_white.png') }}" alt="hrConnectum" class="hidden h-full w-auto dark:block" />
</span>
