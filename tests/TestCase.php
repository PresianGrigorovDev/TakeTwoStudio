<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Livewire\Features\SupportAutoInjectedAssets\SupportAutoInjectedAssets;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Livewire remembers "a component rendered in this request" in a static flag. In PHPUnit
        // every test shares one process, so after a Filament/admin request it would inject its
        // <style>/<script> tags into the next test's public page and break PagesRenderTest.
        SupportAutoInjectedAssets::$hasRenderedAComponentThisRequest = false;
    }
}
