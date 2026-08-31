<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\UserController;
use App\Livewire\Care\CarePlanManagerComponent;
use App\Livewire\Dashboard\AnalyticsDashboardComponent;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
// Route::get('/', function () {return view('home');});

Route::get('/', [LoginController::class, 'showLoginForm'])->name('index');
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('register', [RegisterController::class, 'register']);

Route::get('password/forget', function () {
    return view('auth.passwords.email');
})->name('password.forget');
Route::post('password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');


// Family portal — deliberately outside the staff 'auth' group below. See
// routes/family-portal.php for why.
include __DIR__ . '/family-portal.php';

Route::group(['middleware' => ['auth', 'not-family']], function () {
    // logout route
    Route::get('/logout', [LoginController::class, 'logout']);
    Route::get('/clear-cache', [HomeController::class, 'clearCache']);

    // dashboard route
    Route::get('/dashboard', AnalyticsDashboardComponent::class)->name('dashboard');
    Route::get('/profile', function () { return view('pages.profile'); });

    //only those have manage_user permission will get access
    Route::group(['middleware' => 'can:manage_user'], function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/user/create', [UserController::class, 'create']);
        Route::post('/user/create', [UserController::class, 'store'])->name('create-user');
        Route::get('/user/{id}', [UserController::class, 'edit']);
        Route::post('/user/update', [UserController::class, 'update']);
        Route::delete('/user/delete/{id}', [UserController::class, 'delete']);
    });

    //only those have manage_role permission will get access
    Route::group(['middleware' => 'can:manage_role|manage_user'], function () {
        Route::get('/roles', [RolesController::class, 'index']);
        Route::post('/role/create', [RolesController::class, 'create']);
        Route::get('/role/edit/{id}', [RolesController::class, 'edit']);
        Route::post('/role/update', [RolesController::class, 'update']);
        Route::delete('/role/delete/{id}', [RolesController::class, 'delete']);
    });

    //only those have manage_permission permission will get access
    Route::group(['middleware' => 'can:manage_permission|manage_user'], function () {
        Route::get('/permission', [PermissionController::class, 'index']);
        Route::post('/permission/create', [PermissionController::class, 'create']);
        Route::delete('/permission/delete/{id}', [PermissionController::class, 'delete']);
    });

    Route::prefix('care-management')->name('care-management.')->group(function () {
        Route::get('/dashboard', CarePlanManagerComponent::class)->name('dashboard');
        Route::get('/plan', CarePlanManagerComponent::class)->name('plan');
    });
    include __DIR__ . '/rota-payroll.php';
    include __DIR__ . '/medication-management.php';
    include __DIR__ . '/static-data.php';
    include __DIR__ . '/task-management.php';
    include __DIR__ . '/safeguarding-consent-family.php';
    include __DIR__ . '/compliance-governance.php';
    include __DIR__ . '/operational.php';
    // get permissions
    Route::get('get-role-permissions-badge', [PermissionController::class, 'getPermissionBadgeByRole']);

    // Batch 4 (Role & Data Cleanup): the five generic starter-template
    // modules previously included here (demo/themekit pages, an
    // inventory+POS module, an accounting module, a reports module, a
    // settings module) have nothing to do with CareTrust — none of their
    // routes were named or linked from anywhere in this app, and their
    // sidebar entries were already commented out in config/menu.php. They
    // were still reachable by anyone authenticated (not even Family-gated)
    // if you knew or guessed the URL. Un-wired here rather than deleted —
    // this session can't delete files on your machine — so
    // routes/modules/*.php and their controllers/views are inert leftovers,
    // safe to delete by hand. See CHANGES4.md.
});

Route::get('/register', function () {return view('auth.register');})->name('register');
