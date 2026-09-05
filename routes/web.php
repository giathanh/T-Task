<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\WikiController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', HomeController::class)->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

    Route::get('/projects/{project}/issues', [IssueController::class, 'index'])->name('issues.index');
    Route::get('/projects/{project}/issues/create', [IssueController::class, 'create'])->name('issues.create');
    Route::post('/projects/{project}/issues', [IssueController::class, 'store'])->name('issues.store');

    Route::scopeBindings()->group(function () {
        Route::get('/projects/{project}/issues/{issue}', [IssueController::class, 'show'])->name('issues.show');
        Route::get('/projects/{project}/issues/{issue}/edit', [IssueController::class, 'edit'])->name('issues.edit');
        Route::put('/projects/{project}/issues/{issue}', [IssueController::class, 'update'])->name('issues.update');

        Route::get('/projects/{project}/wiki', [WikiController::class, 'index'])->name('projects.wiki.index');
        Route::get('/projects/{project}/wiki/create', [WikiController::class, 'create'])->name('projects.wiki.create');
        Route::post('/projects/{project}/wiki', [WikiController::class, 'store'])->name('projects.wiki.store');
        Route::get('/projects/{project}/wiki/{wikiPage}', [WikiController::class, 'show'])->name('projects.wiki.show');
        Route::get('/projects/{project}/wiki/{wikiPage}/edit', [WikiController::class, 'edit'])->name('projects.wiki.edit');
        Route::put('/projects/{project}/wiki/{wikiPage}', [WikiController::class, 'update'])->name('projects.wiki.update');
        Route::delete('/projects/{project}/wiki/{wikiPage}', [WikiController::class, 'destroy'])->name('projects.wiki.destroy');
    });
});

require __DIR__.'/auth.php';
