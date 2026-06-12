{{--
    hrConnectum logomark override (additive layer).
    Used on small screens in place of cachet::logomark. Same brand wordmark,
    sized via the caller's classes (e.g. "h-8 w-auto sm:hidden").
--}}
<span {{ $attributes }}>
    <img src="{{ asset('vendor/hrconnectum/hr_logo_black.png') }}" alt="hrConnectum" class="h-full w-auto dark:hidden" />
    <img src="{{ asset('vendor/hrconnectum/hr_logo_white.png') }}" alt="hrConnectum" class="hidden h-full w-auto dark:block" />
</span>
