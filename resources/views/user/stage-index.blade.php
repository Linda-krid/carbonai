@extends('layouts.dashboard')

@section('title', $title)
@section('hide-page-heading', true)

@section('content')
    @php
        $items = collect($items ?? []);
        $layout = $layout ?? 'list';
        $progressStep = (int) ($progressStep ?? 1);
    @endphp

    <div class="mx-auto max-w-6xl">
        @if ($layout === 'banner')
            <section class="overflow-hidden rounded-2xl bg-[#25457d] px-8 py-7 text-white shadow-sm">
                <div class="max-w-3xl">
                    <div class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-100">{{ $step }}</div>
                    <h1 class="mt-3 text-3xl font-bold">{{ $title }}</h1>
                    <p class="mt-3 text-sm leading-6 text-blue-100">{{ $subtitle }}</p>
                    <div class="mt-6 flex gap-2">
                        @for ($index = 1; $index <= 3; $index++)
                            <span class="h-1 w-8 rounded-full {{ $index <= $progressStep ? 'bg-[#16bd83]' : 'bg-white/25' }}"></span>
                        @endfor
                    </div>
                </div>
            </section>
        @else
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-xl text-[#16bd83]">{{ $icon }}</span>
                        <div>
                            <h1 class="font-bold text-[#0f1f3a]">{{ $title }}</h1>
                            <p class="mt-1 text-sm text-slate-500">{{ $items->count() }} élément(s) disponible(s)</p>
                        </div>
                    </div>
                    <a href="{{ $actionUrl }}" class="inline-flex items-center justify-center rounded-xl bg-[#213d70] px-5 py-3 text-sm font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-[#183768]">
                        {{ $actionLabel }}
                    </a>
                </div>

                @if ($items->isEmpty())
                    <div class="min-h-28"></div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach ($items as $item)
                            <div class="flex flex-col gap-4 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="font-bold text-[#0f1f3a]">{{ $item['title'] }}</div>
                                    <div class="mt-1 text-sm text-slate-500">{{ $item['meta'] ?: 'Donnée disponible' }}</div>
                                </div>
                                <div class="flex flex-wrap items-center gap-3">
                                    <span class="rounded-full bg-slate-50 px-3 py-1 text-xs font-bold text-slate-600">{{ $item['status'] }}</span>
                                    <a href="{{ $item['url'] }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50">
                                        {{ $item['action'] }}
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </div>
@endsection
