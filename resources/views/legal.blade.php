@extends('layouts.app')

@section('title', $page->title_bg . ' | Take Two Studio 1603')
@section('meta_description', $page->title_bg . ' на Take Two Studio 1603 — фотографско и видеографско студио във Варна.')
@section('meta_robots', 'index, follow')

@section('content')
<section class="legal-page py-5" style="margin-top: 80px;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <h1 class="mb-3">{{ $page->title_bg }}</h1>
                @if ($page->effective_date)
                    <p class="text-muted mb-4">
                        <small>Последна актуализация: {{ $page->effective_date->format('d.m.Y') }}</small>
                    </p>
                @endif
                <div class="legal-content">
                    {!! $page->content_bg !!}
                </div>
            </div>
        </div>
    </div>
</section>

@push('styles')
<style>
    .legal-page { background: #fff; color: #222; }
    .legal-page h1 { font-weight: 700; }
    .legal-content h2 { margin-top: 2rem; margin-bottom: 1rem; font-size: 1.4rem; font-weight: 600; }
    .legal-content h3 { margin-top: 1.5rem; margin-bottom: 0.75rem; font-size: 1.15rem; font-weight: 600; }
    .legal-content p, .legal-content ul, .legal-content ol { margin-bottom: 1rem; line-height: 1.7; }
    .legal-content ul, .legal-content ol { padding-left: 1.5rem; }
    .legal-content a { color: #b8860b; text-decoration: underline; }
    .legal-content a:hover { color: #8b6508; }
</style>
@endpush
@endsection
