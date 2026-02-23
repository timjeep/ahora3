<?php

use App\Http\Controllers\AdminClientController;
use App\Http\Controllers\AdminCompanyController;
use App\Http\Controllers\AdminTraceController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminSelectController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\SelectController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('policies/cookies', function () {
    return Inertia::render('policies/Cookies');
})->name('policies.cookies');

Route::get('policies/data-deletion', function () {
    return Inertia::render('policies/DataDeletion');
})->name('policies.data-deletion');

Route::get('policies/privacy', function () {
    return Inertia::render('policies/Privacy');
})->name('policies.privacy');

Route::get('policies/terms', function () {
    return Inertia::render('policies/Terms');
})->name('policies.terms');

Route::get('policies/contact', [FeedbackController::class, 'contact'])->name('policies.contact');
Route::post('/policies/contact', [FeedbackController::class, 'contact'])->name('policies.contact.save');

// Public invitation acceptance routes
Route::get('/invitation/{token}', [InvitationController::class, 'show'])->name('invitation.show');
Route::post('/invitation/{token}/accept', [InvitationController::class, 'accept'])->name('invitation.accept');

Route::middleware(['auth'])->group(function () {

    // Feedback Route
    Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');

    // Route::post('/ui/mode', [UiModeController::class, 'setUiMode'])->name('ui.mode');
    // Route::get('/dashboard', [UiModeController::class, 'setDashboard'])->name('dashboard');

    Route::get('/select/country', [SelectController::class, 'countrySelect'])->name('select.country');
    Route::get('/select/user', [SelectController::class, 'userSelect'])->name('select.user');
    Route::get('/select/company', [SelectController::class, 'companySelect'])->name('select.company');
    Route::get('/select/contractor', [SelectController::class, 'contractorSelect'])->name('select.contractor');
    Route::get('/select/project/{site_id?}', [SelectController::class, 'projectSelect'])->name('select.project');
    Route::get('/select/contractor-job', [SelectController::class, 'contractorJobSelect'])->name('select.contractor-job');
    Route::get('/select/site/{client_id?}', [SelectController::class, 'siteSelect'])->name('select.site');
    Route::get('/select/building/{site_id?}', [SelectController::class, 'buildingSelect'])->name('select.building');
    Route::get('/select/client', [SelectController::class, 'clientSelect'])->name('select.client');
    Route::get('/select/survey/{project_id?}', [SelectController::class, 'surveySelect'])->name('select.survey');
    Route::get('/select/jobSurvey/{client_id?}/{site_id?}/{building_id?}', [SelectController::class, 'jobSurveySelect'])->name('select.jobSurvey');
    Route::get('/select/problem-type', [SelectController::class, 'problemTypeSelect'])->name('select.problemType');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        Route::get('/invite', [InvitationController::class, 'adminInvitationNew'])->name('invitation.new');
        Route::post('/invite', [InvitationController::class, 'adminInvitationCreate'])->name('invitation.create');

        Route::get('/select/role', [AdminSelectController::class, 'roleSelect'])->name('select.role');
        Route::get('/select/country', [AdminSelectController::class, 'countrySelect'])->name('select.country');
        Route::get('/select/user', [AdminSelectController::class, 'userSelect'])->name('select.user');
        Route::get('/select/company', [AdminSelectController::class, 'companySelect'])->name('select.company');
        Route::get('/select/client', [AdminSelectController::class, 'clientSelect'])->name('select.client');
        Route::get('/select/contractor', [AdminSelectController::class, 'contractorSelect'])->name('select.contractor');
        Route::get('/select/project', [AdminSelectController::class, 'projectSelect'])->name('select.project');
        Route::get('/select/contractor-job', [AdminSelectController::class, 'contractorJobSelect'])->name('select.contractor-job');
        Route::get('/select/site', [AdminSelectController::class, 'siteSelect'])->name('select.site');

        Route::get('/users', [AdminUserController::class, 'userList'])->name('user.list');
        Route::get('/user/data', [AdminUserController::class, 'userData'])->name('user.data');
        Route::get('/user/new', [AdminUserController::class, 'userNew'])->name('user.new');
        Route::get('/user/{user}', [AdminUserController::class, 'userEdit'])->name('user.edit');
        Route::post('/user', [AdminUserController::class, 'userCreate'])->name('user.create');
        Route::put('/user/{user}', [AdminUserController::class, 'userUpdate'])->name('user.update');
        Route::delete('/user/{user}', [AdminUserController::class, 'userDelete'])->name('user.delete');

        Route::get('/roles', [AdminUserController::class, 'roleList'])->name('role.list');
        Route::get('/role/data', [AdminUserController::class, 'roleData'])->name('role.data');
        Route::get('/role/new', [AdminUserController::class, 'roleNew'])->name('role.new');
        Route::get('/role/{role}', [AdminUserController::class, 'roleEdit'])->name('role.edit');
        Route::post('/role', [AdminUserController::class, 'roleCreate'])->name('role.create');
        Route::put('/role/{role}', [AdminUserController::class, 'roleUpdate'])->name('role.update');
        Route::delete('/role/{role}', [AdminUserController::class, 'roleDelete'])->name('role.delete');

        Route::get('/companies', [AdminCompanyController::class, 'companyList'])->name('company.list');
        Route::get('/company/data', [AdminCompanyController::class, 'companyData'])->name('company.data');
        Route::get('/company/new', [AdminCompanyController::class, 'companyNew'])->name('company.new');
        Route::get('/company/edit/{company}', [AdminCompanyController::class, 'companyEdit'])->name('company.edit');
        Route::post('/company/create', [AdminCompanyController::class, 'companyCreate'])->name('company.create');
        Route::get('/company/slug', [AdminCompanyController::class, 'companySlug'])->name('company.slug');
        Route::put('/company/{company}', [AdminCompanyController::class, 'companyUpdate'])->name('company.update');
        Route::delete('/company/{company}', [AdminCompanyController::class, 'companyDelete'])->name('company.delete');
        Route::get('/company/goto/{company}', [AdminCompanyController::class, 'companyGoto'])->name('company.goto');

        Route::get('/clients', [AdminClientController::class, 'clientList'])->name('client.list');
        Route::get('/client/data', [AdminClientController::class, 'clientData'])->name('client.data');
        Route::get('/client/new', [AdminClientController::class, 'clientNew'])->name('client.new');
        Route::get('/client/edit/{client}', [AdminClientController::class, 'clientEdit'])->name('client.edit');
        Route::post('/client/create', [AdminClientController::class, 'clientCreate'])->name('client.create');
        Route::put('/client/{client}', [AdminClientController::class, 'clientUpdate'])->name('client.update');
        Route::delete('/client/{client}', [AdminClientController::class, 'clientDelete'])->name('client.delete');
        Route::get('/client/goto/{client}', [AdminClientController::class, 'clientGoto'])->name('client.goto');

        Route::get('/traces', [AdminTraceController::class, 'traceList'])->name('trace.list');
        Route::get('/trace/data', [AdminTraceController::class, 'traceData'])->name('trace.data');
        Route::get('/trace/new', [AdminTraceController::class, 'traceNew'])->name('trace.new');
        Route::get('/trace/{trace}', [AdminTraceController::class, 'traceEdit'])->name('trace.edit');
        Route::post('/trace', [AdminTraceController::class, 'traceCreate'])->name('trace.create');
        Route::put('/trace/{trace}', [AdminTraceController::class, 'traceUpdate'])->name('trace.update');
        Route::delete('/trace/{trace}', [AdminTraceController::class, 'traceDelete'])->name('trace.delete');

        Route::get('/tracefilters', [AdminTraceController::class, 'traceFilterList'])->name('tracefilter.list');
        Route::get('/tracefilter/data', [AdminTraceController::class, 'traceFilterData'])->name('tracefilter.data');
        Route::get('/tracefilter/new', [AdminTraceController::class, 'traceFilterNew'])->name('tracefilter.new');
        Route::get('/tracefilter/{traceFilter}', [AdminTraceController::class, 'traceFilterEdit'])->name('tracefilter.edit');
        Route::post('/tracefilter', [AdminTraceController::class, 'traceFilterCreate'])->name('tracefilter.create');
        Route::put('/tracefilter/{traceFilter}', [AdminTraceController::class, 'traceFilterUpdate'])->name('tracefilter.update');
        Route::delete('/tracefilter/{traceFilter}', [AdminTraceController::class, 'traceFilterDelete'])->name('tracefilter.delete');
    });

    Route::get('/user/invite', [InvitationController::class, 'invitationNew'])->name('user.invite.new');
    Route::post('/user/invite', [InvitationController::class, 'invitationCreate'])->name('user.invite.send');
    Route::get('/users', [UserController::class, 'userList'])->name('user.list');
    Route::get('/user/data', [UserController::class, 'userData'])->name('user.data');
    Route::get('/user/new', [UserController::class, 'userNew'])->name('user.new');
    Route::get('/user/{user}', [UserController::class, 'userEdit'])->name('user.edit');
    Route::post('/user', [UserController::class, 'userCreate'])->name('user.create');
    Route::put('/user/{user}', [UserController::class, 'userUpdate'])->name('user.update');
    Route::delete('/user/{user}', [UserController::class, 'userDelete'])->name('user.delete');
    Route::post('/user/{user}/send-reset-password', [UserController::class, 'sendResetPasswordEmail'])->name('user.sendResetPassword');

    Route::prefix('company')->name('company.')->group(function () {
        Route::get('/dashboard', [CompanyController::class, 'dashboard'])->name('dashboard');

        Route::get('/', [CompanyController::class, 'companyEdit'])->name('edit');
        Route::put('/', [CompanyController::class, 'companyUpdate'])->name('update');

    });

    Route::prefix('client')->name('client.')->group(function () {
        Route::get('/dashboard', [ClientController::class, 'dashboard'])->name('dashboard');

        Route::get('/organization', [ClientController::class, 'clientEdit'])->name('edit');
        Route::put('/organization', [ClientController::class, 'clientUpdate'])->name('update');
    });
});
require __DIR__.'/settings.php';
