@extends('layouts.dashboard')

@section('title', $title)
@section('hide-page-heading', true)

@section('content')
    <div class="mx-auto max-w-4xl">
        <section class="overflow-hidden rounded-2xl bg-[#25457d] px-8 py-7 text-white shadow-sm">
            <div class="max-w-2xl">
                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-blue-100">{{ $eyebrow }}</div>
                <h1 class="mt-3 text-3xl font-bold">{{ $title }}</h1>
                <p class="mt-3 text-sm leading-6 text-blue-100">{{ $subtitle }}</p>
            </div>
        </section>

        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-2xl text-[#16bd83]">
                {{ $icon }}
            </div>
            <h2 class="mt-5 text-xl font-bold text-[#0f1f3a]">{{ $title }}</h2>
            <p class="mx-auto mt-2 max-w-xl text-sm text-slate-500">{{ $subtitle }}</p>
            <a href="{{ $actionRoute }}" class="mt-6 inline-flex rounded-xl bg-[#213d70] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-950/20 transition hover:bg-[#183768]">
                {{ $actionLabel }}
            </a>
        </section>
    </div>
@endsection
