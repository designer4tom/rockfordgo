{{-- App Store --}}
<a href="{{ siteText('shared', 'app_download', 'app_url', '#') }}" class="rr-store-btn" aria-label="{{ __('Download on the App Store') }}">
    <svg width="24" height="28" viewBox="0 0 24 28" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="flex-shrink:0">
        <path d="M20.04 21.6c-.92 1.37-1.88 2.72-3.38 2.74-1.48.03-1.96-.87-3.65-.87-1.69 0-2.21.85-3.62.91-1.45.05-2.55-1.46-3.48-2.81C3.7 18.86 2.2 13.7 4.22 10.24c.97-1.69 2.7-2.75 4.57-2.78 1.42-.03 2.77.96 3.64.96.87 0 2.5-1.19 4.22-1.01.72.03 2.74.29 4.03 2.2-.1.07-2.41 1.42-2.38 4.23.03 3.35 2.94 4.47 2.97 4.48-.03.08-.47 1.6-1.23 3.28zM14.5 3.88c.81-.93 2.15-1.62 3.27-1.67.14 1.3-.38 2.61-1.15 3.54-.77.94-2.03 1.68-3.27 1.57-.17-1.28.46-2.61 1.15-3.44z" fill="currentColor"/>
    </svg>
    <span>
        <small>{{ __('Download on the') }}</small>
        <strong>{{ __('App Store') }}</strong>
    </span>
</a>

{{-- Google Play --}}
<a href="{{ siteText('shared', 'app_download', 'play_url', '#') }}" class="rr-store-btn" aria-label="{{ __('Get it on Google Play') }}">
    <svg width="24" height="26" viewBox="0 0 24 26" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="flex-shrink:0">
        <!-- left blue/teal edge -->
        <path d="M1.5 0.6C1.18 0.78 1 1.18 1 1.72V24.28C1 24.82 1.18 25.22 1.5 25.4L1.6 25.49 13.87 13.22V12.78L1.6 0.51 1.5 0.6Z" fill="url(#gp_a)"/>
        <!-- top yellow segment -->
        <path d="M17.96 17.31L13.87 13.22V12.78L17.97 8.69L18.09 8.76L22.93 11.52C24.29 12.29 24.29 13.71 22.93 14.49L18.09 17.24 17.96 17.31Z" fill="url(#gp_b)"/>
        <!-- bottom green shadow -->
        <path d="M18.09 17.24L13.87 13L1.5 25.4C1.96 25.88 2.7 25.94 3.53 25.47L18.09 17.24Z" fill="url(#gp_c)"/>
        <!-- top red -->
        <path d="M18.09 8.76L3.53 0.53C2.7 0.06 1.96 0.12 1.5 0.6L13.87 13 18.09 8.76Z" fill="url(#gp_d)"/>
        <defs>
            <linearGradient id="gp_a" x1="12.81" y1="1.7" x2="-4.84" y2="13" gradientUnits="userSpaceOnUse">
                <stop stop-color="#00A0FF"/>
                <stop offset="1" stop-color="#00D2FF" stop-opacity=".01"/>
            </linearGradient>
            <linearGradient id="gp_b" x1="25.18" y1="13" x2="0.63" y2="13" gradientUnits="userSpaceOnUse">
                <stop stop-color="#FFD500"/>
                <stop offset="1" stop-color="#FFBC00"/>
            </linearGradient>
            <linearGradient id="gp_c" x1="15.43" y1="15.57" x2="-5.63" y2="37.98" gradientUnits="userSpaceOnUse">
                <stop stop-color="#FF3A44"/>
                <stop offset="1" stop-color="#C31162"/>
            </linearGradient>
            <linearGradient id="gp_d" x1="-1.42" y1="-8.38" x2="8.72" y2="2.49" gradientUnits="userSpaceOnUse">
                <stop stop-color="#32A071"/>
                <stop offset="1" stop-color="#2DA771" stop-opacity=".01"/>
            </linearGradient>
        </defs>
    </svg>
    <span>
        <small>{{ __('Get it on') }}</small>
        <strong>{{ __('Google Play') }}</strong>
    </span>
</a>
