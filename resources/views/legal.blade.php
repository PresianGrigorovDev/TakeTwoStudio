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

@endsection
