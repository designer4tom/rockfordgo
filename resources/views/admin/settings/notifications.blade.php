@extends('layouts.admin')

@section('title', 'Notification Settings')
@section('page_title', 'Notification Settings')

@php($w = adminCan('settings', 'write'))

@section('content')
    <x-admin.settings-tabs />

    <form method="POST" action="{{ route('admin.settings.notifications.update') }}" enctype="multipart/form-data" class="space-y-6 max-w-4xl"
          x-data="{
            fcmResult: null, pusherResult: null, whatsappResult: null, smsResult: null, testing: '', whatsappPhone: '', smsPhone: '',
            smsProvider: '{{ $settings['sms_provider'] }}',
            async test(which, url, body = null) {
                this.testing = which; this[which + 'Result'] = null;
                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                            ...(body ? { 'Content-Type': 'application/json' } : {}),
                        },
                        ...(body ? { body: JSON.stringify(body) } : {}),
                    });
                    const data = await res.json();
                    this[which + 'Result'] = { ok: res.ok, message: data.message || (data.errors ? Object.values(data.errors)[0][0] : 'Request failed.') };
                } catch (e) { this[which + 'Result'] = { ok: false, message: 'Request failed.' }; }
                finally { this.testing = ''; }
            }
          }">
        @csrf

        {{-- FCM — only the service-account JSON is needed --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ __('admin.fcm_firebase') }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">{{ __('admin.fcm_json_only_note') }}</p>

            <div class="space-y-4">
                {{-- How to get the JSON (instruction) --}}
                <div class="rounded-lg bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 p-4 text-sm text-indigo-900 dark:text-indigo-200">
                    <p class="font-semibold mb-2">{{ __('admin.where_to_get_firebase_json') }}</p>
                    <ol class="list-decimal ps-5 space-y-1">
                        <li>{{ __('admin.fcm_step_1') }} (<a href="https://console.firebase.google.com" target="_blank" class="underline">console.firebase.google.com</a>)</li>
                        <li>{{ __('admin.fcm_step_2') }}</li>
                        <li>{{ __('admin.fcm_step_3') }}</li>
                        <li>{{ __('admin.fcm_step_4') }}</li>
                    </ol>
                    <p class="font-semibold mt-3 mb-1">{{ __('admin.fcm_json_looks_like') }}</p>
                    <pre class="bg-white dark:bg-gray-900 border border-indigo-100 dark:border-indigo-800 rounded-lg p-3 text-xs overflow-x-auto text-gray-700 dark:text-gray-300">{
  "type": "service_account",
  "project_id": "your-project-id",
  "private_key_id": "xxxxxxxx",
  "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
  "client_email": "firebase-adminsdk-xxxx@your-project.iam.gserviceaccount.com",
  "client_id": "xxxxxxxx",
  "token_uri": "https://oauth2.googleapis.com/token",
  ...
}</pre>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.firebase_service_account') }} (JSON)</label>
                    @if ($settings['firebase_credentials_set'])
                        <p class="text-xs text-green-600 dark:text-green-400 mb-1">✓ {{ __('admin.file_uploaded_replace_optional') }}</p>
                    @else
                        <p class="text-xs text-red-500 dark:text-red-400 mb-1">{{ __('admin.upload_firebase_json_help') }}</p>
                    @endif
                    <input type="file" name="firebase_credentials" accept="application/json,.json" {{ $w ? '' : 'disabled' }} class="block w-full text-sm text-gray-600 dark:text-gray-300 file:me-3 file:rounded-lg file:border-0 file:bg-indigo-50 dark:file:bg-indigo-900/20 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 dark:file:text-indigo-300">
                </div>
                @if ($w)
                    <div class="flex items-center gap-3">
                        <button type="button" @click="test('fcm', '{{ route('admin.settings.notifications.test-fcm') }}')" :disabled="testing==='fcm'" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"><span x-text="testing==='fcm' ? '{{ __('admin.testing') }}' : '{{ __('admin.test_fcm') }}'"></span></button>
                        <p x-show="fcmResult" x-cloak class="text-sm" :class="fcmResult?.ok ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="fcmResult?.message"></p>
                    </div>
                @endif
            </div>
        </section>

        {{-- Pusher --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.pusher') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.app_id') }}</label>
                    <input name="pusher_app_id" value="{{ $settings['pusher_app_id'] }}" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.key') }}</label>
                    <input name="pusher_key" value="{{ $settings['pusher_key'] }}" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.secret') }}</label>
                    @if ($settings['pusher_secret_set'])<p class="text-xs text-gray-400 dark:text-gray-400 mb-1">{{ __('admin.saved_leave_blank') }}</p>@endif
                    <input name="pusher_secret" type="password" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm font-mono" placeholder="••••••">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.cluster') }}</label>
                    <input name="pusher_cluster" value="{{ $settings['pusher_cluster'] }}" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm">
                </div>
            </div>
            @if ($w)
                <div class="flex items-center gap-3 mt-4">
                    <button type="button" @click="test('pusher', '{{ route('admin.settings.notifications.test-pusher') }}')" :disabled="testing==='pusher'" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"><span x-text="testing==='pusher' ? '{{ __('admin.testing') }}' : '{{ __('admin.test_pusher') }}'"></span></button>
                    <p x-show="pusherResult" x-cloak class="text-sm" :class="pusherResult?.ok ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="pusherResult?.message"></p>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-400 mt-1">{{ __('admin.save_credentials_first') }}</p>
            @endif
        </section>

        {{-- WhatsApp — OTP delivery --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-1">{{ __('admin.whatsapp_otp') }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.whatsapp_otp_note') }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                {{ __('admin.whatsapp_recommended') }}
                <a href="https://wasendpilot.com" target="_blank" rel="noopener" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">wasendpilot.com</a>
            </p>

            @if ($settings['whatsapp_test_mode'])
                <div class="mb-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3 text-sm text-amber-800 dark:text-amber-200">
                    {{ __('admin.whatsapp_test_mode_warning') }}
                </div>
            @endif

            <label class="flex items-center gap-2 mb-4">
                <input type="hidden" name="whatsapp_enabled" value="0">
                <input type="checkbox" name="whatsapp_enabled" value="1" @checked($settings['whatsapp_enabled']) {{ $w ? '' : 'disabled' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600">
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('admin.whatsapp_enable_otp') }}</span>
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.api_base_url') }}</label>
                    <input name="whatsapp_api_url" value="{{ $settings['whatsapp_api_url'] }}" {{ $w ? '' : 'disabled' }} placeholder="https://api.example.com/api" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.instance_id') }}</label>
                    <input name="whatsapp_instance_id" value="{{ $settings['whatsapp_instance_id'] }}" {{ $w ? '' : 'disabled' }} placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm font-mono">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.api_key') }}</label>
                    @if ($settings['whatsapp_api_key_set'])<p class="text-xs text-gray-400 dark:text-gray-400 mb-1">{{ __('admin.saved_leave_blank') }}</p>@endif
                    <input name="whatsapp_api_key" type="password" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm font-mono" placeholder="••••••">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.otp_message_template') }}</label>
                    <textarea name="whatsapp_otp_template" rows="3" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm">{{ $settings['whatsapp_otp_template'] }}</textarea>
                    <p class="text-xs text-gray-400 dark:text-gray-400 mt-1">{{ __('admin.otp_template_placeholders') }}</p>
                </div>
            </div>

            @if ($w)
                <div class="flex flex-wrap items-center gap-3 mt-4">
                    <input type="text" x-model="whatsappPhone" placeholder="{{ __('admin.test_phone_placeholder') }}" class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm w-56">
                    <button type="button" @click="test('whatsapp', '{{ route('admin.settings.notifications.test-whatsapp') }}', { phone: whatsappPhone })" :disabled="testing==='whatsapp' || !whatsappPhone" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"><span x-text="testing==='whatsapp' ? '{{ __('admin.testing') }}' : '{{ __('admin.send_test_message') }}'"></span></button>
                    <p x-show="whatsappResult" x-cloak class="text-sm" :class="whatsappResult?.ok ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="whatsappResult?.message"></p>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-400 mt-1">{{ __('admin.save_credentials_first') }}</p>
            @endif
        </section>

        {{-- SMS --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.sms_optional') }}</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.provider') }}</label>
                    <select name="sms_provider" x-model="smsProvider" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm">
                        @foreach (['none' => __('admin.none_provider'), 'twilio' => __('admin.twilio'), 'custom' => __('admin.custom')] as $val => $label)
                            <option value="{{ $val }}" @selected($settings['sms_provider'] === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.api_key') }} <span x-show="smsProvider==='twilio'" x-cloak class="text-gray-400">({{ __('admin.twilio_account_sid') }})</span></label>
                    <input name="sms_api_key" value="{{ $settings['sms_api_key'] }}" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm font-mono">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.sender_id') }} <span x-show="smsProvider==='twilio'" x-cloak class="text-gray-400">({{ __('admin.twilio_from_number') }})</span></label>
                    <input name="sms_sender_id" value="{{ $settings['sms_sender_id'] }}" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm" placeholder="+14155238886">
                </div>
                {{-- Twilio Auth Token (secret, encrypted at rest) --}}
                <div x-show="smsProvider==='twilio'" x-cloak>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.api_secret') }} <span class="text-gray-400">({{ __('admin.twilio_auth_token') }})</span></label>
                    @if ($settings['sms_api_secret_set'])<p class="text-xs text-gray-400 dark:text-gray-400 mb-1">{{ __('admin.saved_leave_blank') }}</p>@endif
                    <input name="sms_api_secret" type="password" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm font-mono" placeholder="••••••">
                </div>
                {{-- Custom gateway endpoint --}}
                <div x-show="smsProvider==='custom'" x-cloak class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('admin.api_url') }}</label>
                    <input name="sms_api_url" value="{{ $settings['sms_api_url'] }}" {{ $w ? '' : 'disabled' }} class="block w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2.5 text-sm font-mono" placeholder="https://gateway.example.com/send">
                    <p class="text-xs text-gray-400 dark:text-gray-400 mt-1">{{ __('admin.sms_custom_help') }}</p>
                </div>
            </div>

            @if ($w)
                <div x-show="smsProvider !== 'none'" x-cloak class="flex flex-wrap items-center gap-3 mt-4">
                    <input type="text" x-model="smsPhone" placeholder="{{ __('admin.test_phone_placeholder') }}" class="rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 px-3 py-2 text-sm w-56">
                    <button type="button" @click="test('sms', '{{ route('admin.settings.notifications.test-sms') }}', { phone: smsPhone })" :disabled="testing==='sms' || !smsPhone" class="rounded-lg border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50"><span x-text="testing==='sms' ? '{{ __('admin.testing') }}' : '{{ __('admin.send_test_message') }}'"></span></button>
                    <p x-show="smsResult" x-cloak class="text-sm" :class="smsResult?.ok ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'" x-text="smsResult?.message"></p>
                </div>
                <p x-show="smsProvider !== 'none'" x-cloak class="text-xs text-gray-400 dark:text-gray-400 mt-1">{{ __('admin.save_credentials_first') }}</p>
            @endif
        </section>

        {{-- Notification Rules --}}
        <section class="bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 shadow-sm p-6">
            <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-4">{{ __('admin.notification_rules') }}</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2 text-start font-medium">{{ __('admin.event') }}</th>
                            <th class="px-3 py-2 text-center font-medium">{{ __('admin.push') }}</th>
                            <th class="px-3 py-2 text-center font-medium">{{ __('admin.sms') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @foreach ($rules as $event => $cfg)
                            <tr>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $ruleLabels[$event] ?? $event }}</td>
                                <td class="px-3 py-2 text-center">
                                    <input type="hidden" name="rules[{{ $event }}][push]" value="0">
                                    <input type="checkbox" name="rules[{{ $event }}][push]" value="1" @checked($cfg['push']) {{ $w ? '' : 'disabled' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600">
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <input type="hidden" name="rules[{{ $event }}][sms]" value="0">
                                    <input type="checkbox" name="rules[{{ $event }}][sms]" value="1" @checked($cfg['sms']) {{ $w ? '' : 'disabled' }} class="rounded border-gray-300 dark:border-gray-600 text-indigo-600">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        @if ($w)
            <button class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">{{ __('admin.save_settings') }}</button>
        @endif
    </form>
@endsection
