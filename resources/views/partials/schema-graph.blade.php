@php
    // Child views have already run, so their @section('title'/'meta_description'/'og_image') values are available here.
    $seoTitle = trim($__env->yieldContent('meta_title', $__env->yieldContent('title', 'Take Two Studio 1603')));
    $seoDescription = trim($__env->yieldContent('meta_description', ''));
    $seoImage = trim($__env->yieldContent('og_image', asset('css/img/social-share-cover.jpg'))) ?: null;
    $seoGraph = app(\App\Support\Seo\JsonLdGraph::class)->toArray($seoTitle, $seoDescription, $seoImage);
@endphp
<script type="application/ld+json">{!! json_encode($seoGraph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
