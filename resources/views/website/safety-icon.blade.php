@switch($icon)
    @case('shield')
        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 3.5 19 6v5.2c0 4.35-2.82 8.25-7 9.55-4.18-1.3-7-5.2-7-9.55V6l7-2.5Z"/>
            <path d="m9.2 12 1.8 1.8 3.9-4.1"/>
        </svg>
        @break
    @case('pin')
        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 21s6.5-5.6 6.5-11.3a6.5 6.5 0 1 0-13 0C5.5 15.4 12 21 12 21Z"/>
            <circle cx="12" cy="9.8" r="2.4"/>
        </svg>
        @break
    @case('bell')
        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7Z"/>
            <path d="M13.7 20a2 2 0 0 1-3.4 0"/>
        </svg>
        @break
    @case('user')
        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="8" r="3.4"/>
            <path d="M5.5 20a6.5 6.5 0 0 1 13 0"/>
        </svg>
        @break
    @case('users')
        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="10" cy="8" r="3"/>
            <path d="M4 19a6 6 0 0 1 12 0"/>
            <path d="M17 10a2.5 2.5 0 0 1 0 5"/>
            <path d="M18.5 19a5 5 0 0 0-2-4"/>
        </svg>
        @break
    @case('phone')
        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/>
            <path d="M10 18h4M9 7l6 6M15 7l-6 6"/>
        </svg>
        @break
    @case('star')
        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
            <path d="m12 3 2.75 5.57 6.15.9-4.45 4.34 1.05 6.12L12 17.05l-5.5 2.88 1.05-6.12L3.1 9.47l6.15-.9L12 3Z"/>
        </svg>
        @break
    @case('lock')
        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
            <rect x="5" y="10" width="14" height="10" rx="2"/>
            <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2"/>
        </svg>
        @break
    @case('car')
        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M4.2 13.9h15.6l-1-4.2A2.85 2.85 0 0 0 16.05 7.5h-8.1A2.85 2.85 0 0 0 5.2 9.7l-1 4.2Z"/>
            <path d="M7.7 9.6h8.6"/>
            <path d="M5.7 13.9v2.9M18.3 13.9v2.9M9.1 17.3h5.8"/>
            <circle cx="7.9" cy="14.35" r=".82" fill="currentColor" stroke="none"/>
            <circle cx="16.1" cy="14.35" r=".82" fill="currentColor" stroke="none"/>
        </svg>
        @break
    @case('seat')
        <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true">
            <path d="M7 4v8a3 3 0 0 0 3 3h7"/>
            <path d="M7 12h8a3 3 0 0 1 3 3v5"/>
            <path d="M6 20h13"/>
        </svg>
        @break
@endswitch
