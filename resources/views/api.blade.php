@extends('layouts.main')
@section('title', 'REST API')
@section('content')
    <x-page-header title="{{ __('REST API') }}" subtitle="{{ __('REST API with Laravel Passport bearer-token authentication') }}"
        icon="ik ik-code"
        :breadcrumbs="['Home' => url('dashboard'), 'Documentation' => '#', 'REST API' => null]" />

    @php
        $baseUrl = url('/api');
        $endpointGroups = [
            'auth' => [
                'title' => __('Authentication'),
                'icon' => 'ik ik-shield',
                'description' => __('Obtain and revoke access tokens.'),
                'items' => [
                    ['POST', '/api/v1/login', __('Authenticate with email & password to receive an access token.'), '{email, password}'],
                    ['POST', '/api/v1/change-password', __('Change the password of the authenticated user.'), '{old_password, password, password_confirmation}'],
                    ['GET', '/api/v1/logout', __('Revoke the current access token.'), ''],
                ],
            ],
            'profile' => [
                'title' => __('Profile'),
                'icon' => 'ik ik-user',
                'description' => __('Read and update the authenticated user profile.'),
                'items' => [
                    ['GET', '/api/v1/profile', __('Get the authenticated user profile.'), ''],
                    ['POST', '/api/v1/update-profile', __('Update name and email of the authenticated user.'), '{name, email}'],
                ],
            ],
            'users' => [
                'title' => __('Users'),
                'icon' => 'ik ik-users',
                'description' => __('Manage application users and their roles.'),
                'items' => [
                    ['GET', '/api/v1/users', __('List all users.'), ''],
                    ['POST', '/api/v1/user/create', __('Create a new user.'), '{name, email, password, password_confirmation, role[]}'],
                    ['GET', '/api/v1/user/{id}', __('Get a single user by id.'), ''],
                    ['GET', '/api/v1/user/delete/{id}', __('Delete a user by id.'), ''],
                    ['POST', '/api/v1/user/change-role/{id}', __('Update the roles assigned to a user.'), '{role[]}'],
                ],
            ],
            'roles' => [
                'title' => __('Roles'),
                'icon' => 'ik ik-award',
                'description' => __('Manage roles and the permissions they grant.'),
                'items' => [
                    ['GET', '/api/v1/roles', __('List all roles.'), ''],
                    ['POST', '/api/v1/role/create', __('Create a new role with permissions.'), '{role, permissions[]}'],
                    ['GET', '/api/v1/role/{id}', __('Get a single role by id.'), ''],
                    ['GET', '/api/v1/role/delete/{id}', __('Delete a role by id.'), ''],
                    ['POST', '/api/v1/role/change-permission/{id}', __('Update the permissions assigned to a role.'), '{permissions[]}'],
                ],
            ],
            'permissions' => [
                'title' => __('Permissions'),
                'icon' => 'ik ik-key',
                'description' => __('Manage the individual permissions of the system.'),
                'items' => [
                    ['GET', '/api/v1/permissions', __('List all permissions.'), ''],
                    ['POST', '/api/v1/permission/create', __('Create a new permission.'), '{permission}'],
                    ['GET', '/api/v1/permission/{id}', __('Get a single permission by id.'), ''],
                    ['GET', '/api/v1/permission/delete/{id}', __('Delete a permission by id.'), ''],
                ],
            ],
        ];

        $methodBadge = function ($method) {
            return match ($method) {
                'GET' => 'bg-green-100 text-green-700',
                'POST' => 'bg-primary-100 text-primary-700',
                'PUT', 'PATCH' => 'bg-amber-100 text-amber-700',
                'DELETE' => 'bg-accent-100 text-accent-700',
                default => 'bg-gray-100 text-gray-700',
            };
        };
    @endphp

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
        {{-- Main content --}}
        <div class="space-y-5 lg:col-span-9">
            {{-- Overview --}}
            <x-card class="relative" icon="ik ik-info" :subtitle="__('How to talk to the Radminly API')">
                <x-slot:header>{{ __('Overview') }}</x-slot:header>
                <span class="absolute right-5 top-5"><x-badge color="danger">{{ __('Passport') }}</x-badge></span>

                <p class="max-w-3xl text-sm leading-relaxed text-gray-600">
                    {{ __('The Radminly API is a low-level HTTP-based API for a Laravel admin starter kit that you can use to create, read, update and delete resources programmatically. It uses') }}
                    <a class="font-medium text-accent-600 hover:underline" href="https://laravel.com/docs/passport" target="_blank" rel="noopener">{{ __('Laravel Passport') }}</a>
                    {{ __('for OAuth2 / bearer-token authentication. Every protected request must include a valid access token.') }}
                </p>

                <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Base URL') }}</p>
                        <div x-data="apiCopy('{{ $baseUrl }}')" class="group relative">
                            <pre class="overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs font-mono text-slate-100">{{ $baseUrl }}</pre>
                            <button type="button" @click="copy"
                                class="absolute right-2 top-2 rounded-md bg-white/10 px-2 py-1 text-[11px] font-medium text-gray-200 backdrop-blur transition hover:bg-white/20">
                                <span x-show="!copied"><i class="ik ik-copy"></i> {{ __('Copy') }}</span>
                                <span x-show="copied" class="text-green-300" x-cloak>{{ __('Copied!') }}</span>
                            </button>
                        </div>
                    </div>
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Format') }}</p>
                        <div class="flex h-full flex-wrap content-start gap-2 rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <x-badge color="primary">JSON</x-badge>
                            <x-badge color="gray">HTTPS</x-badge>
                            <x-badge color="gray">OAuth2</x-badge>
                            <x-badge color="success">Bearer Token</x-badge>
                        </div>
                    </div>
                </div>

                <p class="mb-2 mt-5 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Required Headers') }}</p>
                <div x-data="apiCopy()" class="group relative">
                    <pre x-ref="code" class="overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs font-mono text-slate-100">Authorization: Bearer &lt;your-access-token&gt;
Accept: application/json
Content-Type: application/json</pre>
                    <button type="button" @click="copy"
                        class="absolute right-2 top-2 rounded-md bg-white/10 px-2 py-1 text-[11px] font-medium text-gray-200 backdrop-blur transition hover:bg-white/20">
                        <span x-show="!copied"><i class="ik ik-copy"></i> {{ __('Copy') }}</span>
                        <span x-show="copied" class="text-green-300" x-cloak>{{ __('Copied!') }}</span>
                    </button>
                </div>
            </x-card>

            {{-- Authentication --}}
            <x-card id="group-auth" icon="ik ik-shield" :subtitle="__('Request a token, then send it on every call')">
                <x-slot:header>{{ __('Authentication') }}</x-slot:header>

                <p class="text-sm text-gray-600">
                    {{ __('Send a POST request with your credentials. On success an') }}
                    <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-accent-600">access_token</code>
                    {{ __('is returned, which you attach as a Bearer token on subsequent requests.') }}
                </p>

                <p class="mb-2 mt-5 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Example Request') }}</p>
                <div x-data="apiCopy()" class="group relative">
                    <pre x-ref="code" class="overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs font-mono text-slate-100">curl -X POST {{ $baseUrl }}/v1/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@test.com", "password": "1234"}'</pre>
                    <button type="button" @click="copy"
                        class="absolute right-2 top-2 rounded-md bg-white/10 px-2 py-1 text-[11px] font-medium text-gray-200 backdrop-blur transition hover:bg-white/20">
                        <span x-show="!copied"><i class="ik ik-copy"></i> {{ __('Copy') }}</span>
                        <span x-show="copied" class="text-green-300" x-cloak>{{ __('Copied!') }}</span>
                    </button>
                </div>

                <p class="mb-2 mt-5 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Example Response') }}</p>
                <div x-data="apiCopy()" class="group relative">
                    <pre x-ref="code" class="overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs font-mono text-slate-100">{
    "success": true,
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_in": 31536000,
        "user": {
            "id": 1,
            "name": "Admin",
            "email": "admin@test.com"
        }
    }
}</pre>
                    <button type="button" @click="copy"
                        class="absolute right-2 top-2 rounded-md bg-white/10 px-2 py-1 text-[11px] font-medium text-gray-200 backdrop-blur transition hover:bg-white/20">
                        <span x-show="!copied"><i class="ik ik-copy"></i> {{ __('Copy') }}</span>
                        <span x-show="copied" class="text-green-300" x-cloak>{{ __('Copied!') }}</span>
                    </button>
                </div>

                <div class="mt-5 flex items-start gap-3 rounded-lg border border-primary-100 bg-primary-50 p-4 text-sm text-gray-700">
                    <i class="ik ik-info mt-0.5 text-primary-500"></i>
                    <span>{{ __('Attach the returned token to every protected request using the') }}
                        <code class="rounded bg-white px-1.5 py-0.5 text-xs text-accent-600">Authorization: Bearer &lt;token&gt;</code>
                        {{ __('header. The part after "Bearer" is the access token.') }}</span>
                </div>
            </x-card>

            {{-- Endpoint groups --}}
            @foreach ($endpointGroups as $key => $group)
                <x-card id="group-{{ $key }}" no-padding :icon="$group['icon']" :subtitle="$group['description']">
                    <x-slot:header>{{ $group['title'] }}</x-slot:header>

                    <div class="divide-y divide-gray-50">
                        @foreach ($group['items'] as [$method, $path, $desc, $params])
                            <div class="flex flex-col gap-2 px-5 py-4 transition hover:bg-gray-50 md:flex-row md:items-center md:gap-4">
                                <div class="md:w-20 shrink-0">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-[11px] font-bold tracking-wide {{ $methodBadge($method) }}">{{ $method }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <code class="block break-all font-mono text-sm text-gray-800">{!! preg_replace('/\{(\w+)\}/', '<span class="text-accent-600">{$1}</span>', $path) !!}</code>
                                    <p class="mt-1 text-xs text-gray-500">{{ $desc }}</p>
                                </div>
                                @if ($params)
                                    <div class="shrink-0 md:max-w-[16rem]">
                                        <code class="block break-all rounded bg-gray-100 px-2 py-1 text-[11px] text-accent-600">{{ $params }}</code>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endforeach
        </div>

        {{-- Sticky in-page table of contents --}}
        <aside class="lg:col-span-3">
            <div class="lg:sticky lg:top-20">
                <x-card class="!p-4" no-padding>
                    <div class="px-4 pt-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('On this page') }}</p>
                    </div>
                    <nav class="mt-2 flex flex-col p-2 text-sm">
                        <a href="#group-auth" class="flex items-center gap-2 rounded-lg px-3 py-2 text-gray-600 transition hover:bg-gray-50 hover:text-primary-600">
                            <i class="ik ik-shield text-xs text-gray-400"></i>{{ __('Authentication') }}
                        </a>
                        @foreach ($endpointGroups as $key => $group)
                            <a href="#group-{{ $key }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-gray-600 transition hover:bg-gray-50 hover:text-primary-600">
                                <i class="{{ $group['icon'] }} text-xs text-gray-400"></i>{{ $group['title'] }}
                            </a>
                        @endforeach
                    </nav>
                    <div class="border-t border-gray-100 p-4">
                        <a href="https://documenter.getpostman.com/view/11223504/Szmh1vqc?version=latest" target="_blank" rel="noopener">
                            <x-button variant="outline" size="sm" class="w-full">
                                <i class="ik ik-external-link mr-1"></i>{{ __('Postman Docs') }}
                            </x-button>
                        </a>
                    </div>
                </x-card>
            </div>
        </aside>
    </div>

    <script>
        function apiCopy(text = null) {
            return {
                copied: false,
                copy() {
                    const value = text ?? (this.$refs.code ? this.$refs.code.textContent : '');
                    navigator.clipboard.writeText(value).then(() => {
                        this.copied = true;
                        setTimeout(() => (this.copied = false), 1500);
                    });
                },
            };
        }
    </script>
@endsection
