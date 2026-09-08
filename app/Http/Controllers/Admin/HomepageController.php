<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Homepage;
use App\Services\AuditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomepageController extends Controller
{
    public function edit(): View
    {
        return view('admin.homepage', ['fields' => Arr::dot(Homepage::main()->content)]);
    }

    public function update(Request $request, AuditRecorder $audit): RedirectResponse
    {
        $page = Homepage::main();
        $flat = Arr::dot($page->content);
        $rules = [];
        foreach ($flat as $key => $value) {
            if (str_ends_with($key, '.id')) {
                continue;
            }
            $rules['content.'.$key] = is_bool($value) ? ['required', 'boolean'] : (is_int($value) ? ['required', 'integer', 'min:0', 'max:1000'] : ['nullable', 'string', 'max:5000']);
        }
        $rules['content.contact.phone'] = ['nullable', 'regex:/^\+?[0-9 ()-]{7,25}$/'];
        $rules['content.contact.whatsapp'] = ['nullable', 'regex:/^[0-9]{7,15}$/'];
        $rules['content.contact.email'] = ['nullable', 'email', 'max:254'];
        foreach (['hero.line_one', 'hero.line_two', 'hero.line_three', 'seo.title', 'seo.description'] as $key) {
            $rules['content.'.$key] = ['required', 'string', 'max:500'];
        }
        $validated = $request->validate($rules)['content'];
        $updated = $page->content;
        foreach (Arr::dot($validated) as $key => $value) {
            if (! array_key_exists($key, Arr::dot($page->content)) || str_ends_with($key, '.id')) {
                continue;
            }
            $original = Arr::get($updated, $key);
            Arr::set($updated, $key, is_bool($original) ? (bool) $value : (is_int($original) ? (int) $value : ($value ?? '')));
        }
        DB::transaction(function () use ($page, $updated, $audit) {
            $page->update(['content' => $updated]);
            $audit->record('homepage.updated', $page);
        });

        return back()->with('status', 'Homepage updated.');
    }
}
