<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnquiryRequest;
use App\Models\Homepage;
use App\Models\SiteSetting;
use App\Services\EnquiryCapture;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HomepageController extends Controller
{
    public function index(): View
    {
        $content = SiteSetting::applyTo(Homepage::main()->content);
        $sections = collect($content['sections'])->where('enabled', true)->sortBy('order');

        return view('home', compact('content', 'sections'));
    }

    public function store(StoreEnquiryRequest $request): RedirectResponse
    {
        app(EnquiryCapture::class)->store($request);

        return redirect()->to(route('home').'#contact')->with('enquiry_sent', true);
    }
}
