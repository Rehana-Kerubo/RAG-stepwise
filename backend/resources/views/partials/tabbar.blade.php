{{--
    Bottom tab bar. The tabs depend on the user's role.
    Pages that include this need bottom padding: calc(env(safe-area-inset-bottom) + 96px).
--}}
@php
    // SVG paths for the tab icons (24x24, drawn with a stroke)
    $icons = [
        'home' => '<path d="M3 11l9-8 9 8"/><path d="M5 10v10h14V10"/>',
        'list' => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20a7 7 0 0 1 14 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M22 20a7 7 0 0 0-4-6.3"/>',
        'bell' => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/>',
    ];

    if (auth()->user()->isManager()) {
        $tabs = [
            ['url' => '/manager', 'label' => 'Overview', 'icon' => 'home', 'active' => request()->is('manager', 'manager/sessions/*', 'manager/insights')],
            ['url' => '/manager/agents', 'label' => 'Agents', 'icon' => 'users', 'active' => request()->is('manager/agents*')],
            ['url' => '/manager/alerts', 'label' => 'Alerts', 'icon' => 'bell', 'active' => request()->is('manager/alerts'),
             'badge' => \App\Models\Alert::where('is_resolved', false)->count()],
            ['url' => '/profile', 'label' => 'Profile', 'icon' => 'user', 'active' => request()->is('profile')],
        ];
    } else {
        $tabs = [
            ['url' => '/agent', 'label' => 'Home', 'icon' => 'home', 'active' => request()->is('agent')],
            ['url' => '/agent/onboardings', 'label' => 'My Onboardings', 'icon' => 'list', 'active' => request()->is('agent/onboardings')],
            ['url' => '/profile', 'label' => 'Profile', 'icon' => 'user', 'active' => request()->is('profile')],
        ];
    }
@endphp

<div class="fixed inset-x-0 bottom-0 z-30" style="view-transition-name: tabbar;">

    <!-- Offline / waiting-to-sync strip (filled in by /js/sync-status.js) -->
    <div id="sync-strip" class="hidden" role="status">
        <div class="mx-auto flex max-w-[480px] flex-wrap items-center justify-center gap-2 px-4 pb-2">
            <span id="offline-text"
                  class="hidden rounded-full bg-ink px-3 py-1.5 text-xs font-semibold text-white shadow">
                You're offline. Your work will sync later
            </span>
            <a id="sync-chip" href="#"
               class="hidden rounded-full bg-amber px-3 py-1.5 text-xs font-bold text-ink shadow"></a>
        </div>
    </div>

    <nav class="border-t border-hairline bg-slip/95 backdrop-blur"
         style="padding-bottom: env(safe-area-inset-bottom);"
         aria-label="Main">
        <div class="mx-auto flex max-w-[480px]">
            @foreach ($tabs as $tab)
                <a href="{{ url($tab['url']) }}"
                   class="tap relative flex flex-1 flex-col items-center justify-center gap-0.5 pb-2 pt-2.5 text-[11px] font-semibold {{ $tab['active'] ? 'text-cobalt' : 'text-muted' }}"
                   @if ($tab['active']) aria-current="page" @endif>

                    <!-- Small bar over the active tab -->
                    <span class="absolute inset-x-6 top-0 h-[3px] rounded-b-full {{ $tab['active'] ? 'bg-cobalt' : 'bg-transparent' }}"></span>

                    <span class="relative">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="{{ $tab['active'] ? '2.4' : '2' }}" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            {!! $icons[$tab['icon']] !!}
                        </svg>

                        @if (! empty($tab['badge']))
                            <span class="absolute -right-2.5 -top-1.5 min-w-[18px] rounded-full bg-amber px-1 text-center text-[10px] font-bold leading-[18px] text-ink">
                                {{ $tab['badge'] }}
                            </span>
                        @endif
                    </span>

                    {{ $tab['label'] }}
                </a>
            @endforeach
        </div>
    </nav>
</div>

<script>
    document.querySelectorAll('nav a.tap').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('nav a.tap').forEach(t => {
                t.classList.replace('text-cobalt', 'text-muted');
                t.querySelector('span.absolute')?.classList.replace('bg-cobalt', 'bg-transparent');
            });
            tab.classList.replace('text-muted', 'text-cobalt');
            tab.querySelector('span.absolute')?.classList.replace('bg-transparent', 'bg-cobalt');
        });
    });
</script>

<script src="/js/sync-status.js" defer></script>
