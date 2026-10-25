@extends('layouts.app')

@section('title', 'StepWise: add agent')

@section('content')
<div class="mx-auto min-h-[100dvh] max-w-[640px] bg-counter px-5 sm:shadow-xl"
     style="padding-top: calc(env(safe-area-inset-top) + 12px); padding-bottom: calc(env(safe-area-inset-bottom) + 96px);">

    <a href="{{ url('/manager/agents') }}" class="tap -ml-1 inline-flex items-center gap-1 text-sm font-semibold text-cobalt-deep">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M15 18l-6-6 6-6"/>
        </svg>
        Agents
    </a>

    <h1 class="mt-2 text-[30px] font-extrabold leading-[1.08] tracking-tight">Add agent</h1>
    <p class="mt-1 text-[15px] leading-snug text-muted">
        The agent signs in with Google using this email. You choose their gigs on the next screen.
    </p>

    <form method="POST" action="{{ url('/manager/agents') }}" class="mt-6 space-y-4">
        @csrf

        @php
            $fields = [
                ['name' => 'name', 'label' => 'Full name', 'type' => 'text', 'auto' => 'name', 'placeholder' => 'e.g. Grace Achieng', 'required' => true],
                ['name' => 'email', 'label' => 'Google email', 'type' => 'email', 'auto' => 'email', 'placeholder' => 'name@gmail.com', 'required' => true],
                ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'auto' => 'tel', 'placeholder' => 'e.g. 0711 000 000', 'required' => true],
                ['name' => 'region', 'label' => 'Region (optional)', 'type' => 'text', 'auto' => 'off', 'placeholder' => 'e.g. Nairobi CBD', 'required' => false],
            ];
        @endphp

        @foreach ($fields as $field)
            <div>
                <label for="{{ $field['name'] }}" class="text-sm font-semibold">{{ $field['label'] }}</label>
                <input id="{{ $field['name'] }}" name="{{ $field['name'] }}" type="{{ $field['type'] }}"
                       value="{{ old($field['name']) }}" placeholder="{{ $field['placeholder'] }}"
                       autocomplete="{{ $field['auto'] }}" @if ($field['required']) required @endif
                       class="mt-1 h-12 w-full rounded-xl border bg-slip px-3 text-[16px] {{ $errors->has($field['name']) ? 'border-red-600' : 'border-hairline' }}">
                @error($field['name'])
                    <p class="mt-1 text-sm font-semibold text-red-700">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        <button type="submit" class="h-14 w-full rounded-xl bg-cobalt text-[17px] font-bold text-white active:bg-cobalt-deep">
            Add agent
        </button>
    </form>

</div>

@include('partials.tabbar')
@endsection
