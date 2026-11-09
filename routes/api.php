<?php

use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\ReportModerationController;
use App\Http\Controllers\Admin\RequestManagementController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\CheckinController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\HeatmapController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SmokeLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/admin/login', [AuthController::class, 'adminLogin']);

// Publicly readable feeds so landing/community/blog render without auth.
Route::get('/challenges', [ChallengeController::class, 'index']);
Route::get('/posts', [PostController::class, 'index']);
Route::get('/blogs', [BlogController::class, 'index']);
Route::get('/broadcast', [BroadcastController::class, 'show']);

/*
|--------------------------------------------------------------------------
| Authenticated member routes (Sanctum bearer token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::post('/profile/resist', [ProfileController::class, 'resist']);

    Route::get('/settings', [SettingsController::class, 'show']);
    Route::put('/settings', [SettingsController::class, 'update']);

    Route::get('/smoke-logs', [SmokeLogController::class, 'index']);
    Route::post('/smoke-logs', [SmokeLogController::class, 'upsert']);

    Route::get('/goals', [GoalController::class, 'index']);
    Route::post('/goals', [GoalController::class, 'store']);
    Route::delete('/goals/{goal}', [GoalController::class, 'destroy']);

    Route::get('/reminders', [ReminderController::class, 'index']);
    Route::put('/reminders/{key}', [ReminderController::class, 'update']);

    Route::get('/heatmap', [HeatmapController::class, 'index']);
    Route::post('/heatmap/toggle', [HeatmapController::class, 'toggle']);
    Route::post('/heatmap/log', [HeatmapController::class, 'logNow']);

    Route::get('/checkins', [CheckinController::class, 'index']);
    Route::post('/checkins', [CheckinController::class, 'store']);

    Route::post('/posts', [PostController::class, 'store']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);
    Route::post('/posts/{post}/react', [PostController::class, 'react']);

    Route::post('/blogs', [BlogController::class, 'store']);
    Route::delete('/blogs/{blog}', [BlogController::class, 'destroy']);
    Route::post('/blogs/{blog}/like', [BlogController::class, 'toggleLike']);

    Route::post('/comments/{type}/{id}', [CommentController::class, 'store'])
        ->whereIn('type', ['post', 'blog']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    Route::post('/challenges/{challenge}/join', [ChallengeController::class, 'toggleJoin']);
    Route::post('/challenges/{challenge}/log-day', [ChallengeController::class, 'logDay']);

    Route::post('/reports', [ReportController::class, 'store']);
    Route::get('/requests', [RequestController::class, 'index']);
    Route::post('/requests', [RequestController::class, 'store']);

    // Real-time peak-craving alerts (SPA polls every ~30s)
    Route::get('/notifications/due', [NotificationController::class, 'due']);
});

/*
|--------------------------------------------------------------------------
| Admin console routes (auth + admin role)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/analytics/overview', [AnalyticsController::class, 'overview']);

    Route::get('/users', [UserManagementController::class, 'index']);
    Route::post('/users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus']);
    Route::post('/users/{user}/reset-avatar', [UserManagementController::class, 'resetAvatar']);

    Route::get('/reports', [ReportModerationController::class, 'index']);
    Route::post('/reports/{report}/resolve', [ReportModerationController::class, 'resolve']);
    Route::delete('/reports/{report}', [ReportModerationController::class, 'destroy']);

    Route::get('/requests', [RequestManagementController::class, 'index']);
    Route::post('/requests/{userRequest}/status', [RequestManagementController::class, 'updateStatus']);
    Route::delete('/requests/{userRequest}', [RequestManagementController::class, 'destroy']);

    Route::post('/broadcast', [BroadcastController::class, 'store']);
    Route::delete('/broadcast', [BroadcastController::class, 'destroy']);

    Route::post('/challenges', [ChallengeController::class, 'store']);
    Route::put('/challenges/{challenge}', [ChallengeController::class, 'update']);
    Route::delete('/challenges/{challenge}', [ChallengeController::class, 'destroy']);
});
