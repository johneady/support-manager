<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function __invoke(Faq $faq): View
    {
        abort_unless($faq->is_published, 404);

        return view('faq-show', ['faq' => $faq]);
    }
}
