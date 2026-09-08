<?php

use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\HomepageController;
use App\Models\AuditLog;
use App\Models\ContentEntry;
use App\Models\Enquiry;
use App\Models\Homepage;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomepageController::class, 'index'])->name('home');
Route::post('/enquiries', [HomepageController::class, 'store'])->middleware('throttle:5,1')->name('enquiries.store');
Route::get('/health', function (Request $request) {
    abort_if(app()->isProduction(), 404);
    $data = ['status' => 'ok', 'environment' => app()->environment(), 'framework' => 'Laravel', 'checkpoint' => 2];

    return $request->acceptsHtml() && ! $request->expectsJson() ? response()->view('health', ['data' => $data]) : response()->json($data);
})->name('health');
Route::middleware(['auth', 'auth.session'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/security', 'admin.security')->middleware('password.confirm')->name('security');
    Route::middleware(['two-factor.required', 'can:admin.view'])->group(function () {
        Route::view('/', 'admin.dashboard')->name('dashboard');
        Route::view('/development', 'checkpoint')->middleware('can:settings.manage')->name('development');
        Route::get('/homepage', [App\Http\Controllers\Admin\HomepageController::class, 'edit'])->middleware('can:pages.edit')->name('homepage.edit');
        Route::put('/homepage', [App\Http\Controllers\Admin\HomepageController::class, 'update'])->middleware('can:pages.edit')->name('homepage.update');
        Route::get('/content', [ContentController::class, 'index'])->middleware('can:pages.view')->name('content.index');
        Route::get('/content/create', [ContentController::class, 'create'])->middleware('can:pages.edit')->name('content.create');
        Route::post('/content', [ContentController::class, 'store'])->middleware('can:pages.edit')->name('content.store');
        Route::get('/content/{entry}/edit', [ContentController::class, 'edit'])->middleware('can:pages.edit')->name('content.edit');
        Route::put('/content/{entry}', [ContentController::class, 'update'])->middleware('can:pages.edit')->name('content.update');
        Route::post('/content/{entry}/status', [ContentController::class, 'transition'])->middleware('can:pages.edit')->name('content.transition');
        Route::post('/content/{entry}/restore', [ContentController::class, 'restore'])->middleware('can:pages.edit')->name('content.restore');
        Route::get('/content/{entry}/preview', [ContentController::class, 'preview'])->middleware('can:pages.edit')->name('content.preview');
        Route::get('/media', [MediaController::class, 'index'])->middleware('can:media.manage')->name('media.index');
        Route::get('/media/create', [MediaController::class, 'create'])->middleware('can:media.manage')->name('media.create');
        Route::post('/media', [MediaController::class, 'store'])->middleware('can:media.manage')->name('media.store');
        Route::get('/media/{media}/edit', [MediaController::class, 'edit'])->middleware('can:media.manage')->name('media.edit');
        Route::put('/media/{media}', [MediaController::class, 'update'])->middleware('can:media.manage')->name('media.update');
        Route::get('/media/{media}/original', [MediaController::class, 'original'])->middleware('can:media.manage')->name('media.original');
        Route::get('/enquiries', fn () => view('admin.enquiries', ['enquiries' => Enquiry::latest()->paginate(20)]))->middleware('can:leads.view')->name('enquiries');
        Route::get('/users', [UserController::class, 'index'])->middleware('can:users.manage')->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->middleware(['can:users.manage', 'password.confirm', 'throttle:20,1'])->name('users.store');
        Route::patch('/users/{user}', [UserController::class, 'update'])->middleware(['can:users.manage', 'password.confirm', 'throttle:20,1'])->name('users.update');
        Route::get('/roles', fn () => view('admin.roles', ['roles' => Role::with('permissions')->orderBy('label')->get()]))->middleware('can:roles.view')->name('roles');
        Route::get('/audit', fn () => view('admin.audit', ['logs' => AuditLog::latest('id')->paginate(30)]))->middleware('can:audit.view')->name('audit');
    });
});

Route::get('/media/{media}', [MediaController::class, 'show'])->name('media.show');
Route::get('/pages/{slug}', function (string $slug) {
    $entry = ContentEntry::where('type', 'page')->where('slug', $slug)->whereNotNull('published_revision_id')->with('publishedRevision')->firstOrFail();
    $payload = $entry->publishedRevision->payload;
    $content = Homepage::main()->content;
    $content['seo'] = ['title' => $payload['title'], 'description' => $payload['description'] ?? ''];
    $sections = collect($content['sections'])->where('enabled', true)->sortBy('order');

    return view('page', compact('entry', 'payload', 'content', 'sections'));
})->name('pages.show');
