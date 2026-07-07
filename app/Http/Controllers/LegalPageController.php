<?php

namespace App\Http\Controllers;

use App\Models\LegalPage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LegalPageController extends Controller
{
    public function show(string $slug)
    {
        $page = LegalPage::where('slug', $slug)
            ->where('is_published', true)
            ->first();

        if (! $page) {
            throw new NotFoundHttpException();
        }

        return view('legal', compact('page'));
    }
}
