<?php

use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\CorporateContentController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CorporateController;
use App\Http\Controllers\HomepageController;
use App\Http\Middleware\RequireTwoFactor;
use App\Models\AuditLog;
use App\Models\ContentEntry;
use App\Models\Enquiry;
use App\Models\Homepage;
use App\Models\SiteSetting;
use App\Services\CorporateContent;
use App\Services\LocationContent;
use App\Services\ProjectContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

Route::get('/', [HomepageController::class, 'index'])->name('home');
foreach (['about' => 'about', 'business' => 'business', 'capabilities' => 'capabilities', 'capabilities/equipment' => 'equipment', 'about/leadership' => 'leadership', 'about/journey' => 'journey', 'about/employee-stories' => 'stories'] as $path => $group) {
    Route::get('/'.$path, [CorporateController::class, 'index'])->defaults('group', $group)->name('corporate.'.$group.'.index');
}
foreach (['about/people/{slug}' => 'team_member', 'about/milestones/{slug}' => 'company_milestone', 'about/employee-stories/{slug}' => 'employee_story', 'business/services/{slug}' => 'service', 'capabilities/equipment/{slug}' => 'equipment', 'about/{slug}' => 'company_page', 'business/{slug}' => 'business_unit', 'capabilities/{slug}' => 'capability'] as $path => $type) {
    Route::get('/'.$path, [CorporateController::class, 'show'])->defaults('type', $type)->name(config('corporate.'.$type.'.route'));
}
Route::get('/locations', fn () => view('locations.index', CorporateContent::layout('Our locations') + ['items' => LocationContent::active(), 'canonical' => route('locations.index')]))->name('locations.index');
Route::get('/locations/{slug}', function (string $slug) {
    $entry = ContentEntry::where('type', 'location')->where('slug', $slug)->whereNotNull('published_revision_id')->with('publishedRevision')->firstOrFail();
    abort_unless(LocationContent::active()->has($entry->id), 404);

    return LocationContent::detail($entry, $entry->publishedRevision->payload);
})->name('locations.show');
Route::get('/projects', function (Request $request) {
    $filters = $request->validate(['status' => ['nullable', Rule::in(ProjectContent::STATUSES)], 'sector' => ['nullable', Rule::in(ProjectContent::SECTORS)], 'city' => 'nullable|string|max:255']);
    $all = ProjectContent::items();
    $items = $all->filter(fn ($item) => collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->every(fn ($value, $key) => ($item[$key] ?? null) === $value));

    return view('projects.index', CorporateContent::layout('Our work defines us') + compact('items', 'filters') + ['cities' => $all->pluck('city')->unique()->sort(), 'canonical' => route('projects.index')]);
})->name('projects.index');
Route::get('/projects/{slug}', function (string $slug) {
    $entry = ContentEntry::where('type', 'project')->where('slug', $slug)->whereNotNull('published_revision_id')->with('publishedRevision')->firstOrFail();

    return ProjectContent::detail($entry, $entry->publishedRevision->payload);
})->name('projects.show');
Route::post('/enquiries', [HomepageController::class, 'store'])->middleware('throttle:5,1')->name('enquiries.store');
Route::get('/health', function (Request $request) {
    abort_if(app()->isProduction(), 404);
    $data = ['status' => 'ok', 'environment' => app()->environment(), 'framework' => 'Laravel', 'checkpoint' => 2];

    return $request->acceptsHtml() && ! $request->expectsJson() ? response()->view('health', ['data' => $data]) : response()->json($data);
})->name('health');
Route::middleware(['auth', 'auth.session'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/security', function (Request $request) {
        if (! RequireTwoFactor::enforced()) {
            $request->session()->forget(['admin.setup_destination', 'admin.setup_recovery_pending']);

            return redirect()->route('admin.dashboard');
        }

        $recent = time() - $request->session()->get('auth.password_confirmed_at', 0) < config('auth.password_timeout', 10800);
        if (! $recent) {
            $request->session()->put('url.intended', route('admin.security'));
        }

        return view('admin.security', ['recentPassword' => $recent]);
    })->name('security');
    Route::post('/security/continue', function (Request $request) {
        abort_unless($request->user()->two_factor_confirmed_at, 403);
        $request->validate(['recovery_saved' => 'accepted']);
        $request->session()->forget('admin.setup_recovery_pending');
        $destination = $request->session()->pull('admin.setup_destination', '/admin');
        if (! is_string($destination) || ! preg_match('~^/admin(?:/|$)~', $destination) || str_starts_with($destination, '/admin/security')) {
            $destination = '/admin';
        }

        return redirect()->to($destination)->with('status', 'Account setup complete. Your workspace is ready.');
    })->middleware('password.confirm')->name('security.continue');
    Route::middleware(['two-factor.required', 'can:admin.view'])->group(function () {
        Route::view('/', 'admin.dashboard')->name('dashboard');
        Route::view('/account', 'admin.account')->name('account');
        Route::get('/locations', [LocationController::class, 'index'])->middleware('can:pages.view')->name('locations.index');
        Route::get('/locations/create', [LocationController::class, 'create'])->middleware('can:pages.create')->name('locations.create');
        Route::post('/locations', [LocationController::class, 'store'])->middleware('can:pages.create')->name('locations.store');
        Route::get('/locations/{entry}/edit', [LocationController::class, 'edit'])->middleware('can:pages.edit')->name('locations.edit');
        Route::put('/locations/{entry}', [LocationController::class, 'update'])->middleware('can:pages.edit')->name('locations.update');
        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('/projects/{entry}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
        Route::put('/projects/{entry}', [ProjectController::class, 'update'])->name('projects.update');
        Route::post('/projects/{entry}/status', [ProjectController::class, 'transition'])->name('projects.transition');
        Route::post('/projects/{entry}/assign', [ProjectController::class, 'assign'])->name('projects.assign');
        Route::get('/projects/{entry}/preview', [ProjectController::class, 'preview'])->name('projects.preview');
        Route::view('/development', 'checkpoint')->middleware('can:settings.manage')->name('development');
        Route::get('/settings', [SettingController::class, 'edit'])->middleware('can:settings.manage')->name('settings.edit');
        Route::put('/settings', [SettingController::class, 'update'])->middleware('can:settings.manage')->name('settings.update');
        Route::get('/corporate', [CorporateContentController::class, 'index'])->middleware('can:pages.view')->name('corporate.index');
        Route::get('/corporate/create', [CorporateContentController::class, 'create'])->middleware('can:pages.create')->name('corporate.create');
        Route::post('/corporate', [CorporateContentController::class, 'store'])->middleware('can:pages.create')->name('corporate.store');
        Route::get('/corporate/{entry}/edit', [CorporateContentController::class, 'edit'])->middleware('can:pages.edit')->name('corporate.edit');
        Route::put('/corporate/{entry}', [CorporateContentController::class, 'update'])->middleware('can:pages.edit')->name('corporate.update');
        Route::get('/homepage', [App\Http\Controllers\Admin\HomepageController::class, 'edit'])->middleware('can:pages.edit')->name('homepage.edit');
        Route::put('/homepage', [App\Http\Controllers\Admin\HomepageController::class, 'update'])->middleware('can:pages.edit')->name('homepage.update');
        Route::get('/content', [ContentController::class, 'index'])->middleware('can:pages.view')->name('content.index');
        Route::get('/content/create', [ContentController::class, 'create'])->middleware('can:pages.create')->name('content.create');
        Route::post('/content', [ContentController::class, 'store'])->middleware('can:pages.create')->name('content.store');
        Route::get('/content/{entry}/edit', [ContentController::class, 'edit'])->middleware('can:pages.edit')->name('content.edit');
        Route::put('/content/{entry}', [ContentController::class, 'update'])->middleware('can:pages.edit')->name('content.update');
        Route::post('/content/{entry}/status', [ContentController::class, 'transition'])->middleware('can:pages.edit')->name('content.transition');
        Route::post('/content/{entry}/restore', [ContentController::class, 'restore'])->middleware('can:pages.edit')->name('content.restore');
        Route::get('/content/{entry}/preview', [ContentController::class, 'preview'])->middleware('can:pages.edit')->name('content.preview');
        Route::get('/media', [MediaController::class, 'index'])->middleware('can:media.manage')->name('media.index');
        Route::get('/media/create', [MediaController::class, 'create'])->middleware(['can:media.manage', 'can:media.upload'])->name('media.create');
        Route::post('/media', [MediaController::class, 'store'])->middleware(['can:media.manage', 'can:media.upload'])->name('media.store');
        Route::get('/media/{media}/edit', [MediaController::class, 'edit'])->middleware(['can:media.manage', 'can:media.edit'])->name('media.edit');
        Route::post('/media/{media}/archive', [MediaController::class, 'archive'])->middleware(['can:media.manage', 'can:media.edit'])->name('media.archive');
        Route::put('/media/{media}', [MediaController::class, 'update'])->middleware(['can:media.manage', 'can:media.edit'])->name('media.update');
        Route::get('/media/{media}/original', [MediaController::class, 'original'])->middleware('can:media.manage')->name('media.original');
        Route::get('/enquiries', fn () => view('admin.enquiries', ['enquiries' => Enquiry::latest()->paginate(20)]))->middleware('can:leads.view')->name('enquiries');
        Route::get('/users', [UserController::class, 'index'])->middleware('can:users.manage')->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->middleware(['can:users.manage', 'password.confirm', 'throttle:20,1'])->name('users.store');
        Route::patch('/users/{user}', [UserController::class, 'update'])->middleware(['can:users.manage', 'password.confirm', 'throttle:20,1'])->name('users.update');
        Route::get('/roles', [RoleController::class, 'index'])->middleware('can:roles.view')->name('roles');
        Route::get('/roles/create', [RoleController::class, 'create'])->middleware(['can:roles.manage', 'password.confirm'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->middleware(['can:roles.manage', 'password.confirm', 'throttle:20,1'])->name('roles.store');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware(['can:roles.manage', 'password.confirm'])->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware(['can:roles.manage', 'password.confirm', 'throttle:20,1'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware(['can:roles.manage', 'password.confirm', 'throttle:20,1'])->name('roles.destroy');
        Route::get('/audit', fn () => view('admin.audit', ['logs' => AuditLog::with('actor')->latest('id')->paginate(30)]))->middleware('can:audit.view')->name('audit');
    });
});

Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');
Route::get('/pages/{slug}', function (string $slug) {
    $entry = ContentEntry::where('type', 'page')->where('slug', $slug)->whereNotNull('published_revision_id')->with('publishedRevision')->firstOrFail();
    $payload = $entry->publishedRevision->payload;
    $content = SiteSetting::applyTo(Homepage::main()->content);
    $content['seo'] = ['title' => $payload['title'], 'description' => $payload['description'] ?? ''];
    $sections = collect($content['sections'])->where('enabled', true)->sortBy('order');

    return view('page', compact('entry', 'payload', 'content', 'sections'));
})->name('pages.show');
