<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\HomepageController;
use App\Models\AuditLog;
use App\Models\Enquiry;
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
        Route::get('/homepage', [App\Http\Controllers\Admin\HomepageController::class, 'edit'])->middleware('can:settings.manage')->name('homepage.edit');
        Route::put('/homepage', [App\Http\Controllers\Admin\HomepageController::class, 'update'])->middleware('can:settings.manage')->name('homepage.update');
        Route::get('/enquiries', fn () => view('admin.enquiries', ['enquiries' => Enquiry::latest()->paginate(20)]))->middleware('can:leads.view')->name('enquiries');
        Route::get('/users', [UserController::class, 'index'])->middleware('can:users.manage')->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->middleware(['can:users.manage', 'password.confirm', 'throttle:20,1'])->name('users.store');
        Route::patch('/users/{user}', [UserController::class, 'update'])->middleware(['can:users.manage', 'password.confirm', 'throttle:20,1'])->name('users.update');
        Route::get('/roles', fn () => view('admin.roles', ['roles' => Role::with('permissions')->orderBy('label')->get()]))->middleware('can:roles.view')->name('roles');
        Route::get('/audit', fn () => view('admin.audit', ['logs' => AuditLog::latest('id')->paginate(30)]))->middleware('can:audit.view')->name('audit');
    });
});
