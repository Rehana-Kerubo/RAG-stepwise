{{-- Gig filter. Needs $gigs, $selectedGig and $action (the page to reload). --}}
<form method="GET" action="{{ url($action) }}" class="mt-6 flex flex-wrap items-center gap-2">
    <label for="gig" class="text-sm font-semibold">Gig</label>

    <select id="gig" name="gig" onchange="this.form.submit()"
            class="rounded-xl border border-hairline bg-slip px-3 py-2 text-sm">
        <option value="">All gigs</option>
        @foreach ($gigs as $gig)
            <option value="{{ $gig->slug }}" @selected($selectedGig?->id === $gig->id)>{{ $gig->name }}</option>
        @endforeach
    </select>

    <noscript>
        <button type="submit" class="rounded-xl bg-ink px-3 py-2 text-sm font-semibold text-white">Filter</button>
    </noscript>
</form>
