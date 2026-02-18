<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardWidgetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\ReminderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavingsGoalController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\TicketController;


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('tickets', TicketController::class);

    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('categories', [CategoryController::class, 'store']);
    Route::put('categories/{categoryId}', [CategoryController::class, 'update']);
    Route::delete('categories/{categoryId}', [CategoryController::class, 'destroy']);

    Route::get('tags', [TagController::class, 'index']);
    Route::post('tags', [TagController::class, 'store']);
    Route::put('tags/{tagId}', [TagController::class, 'update']);
    Route::delete('tags/{tagId}', [TagController::class, 'destroy']);

    Route::get('budgets', [BudgetController::class, 'index']);
    Route::post('budgets', [BudgetController::class, 'store']);
    Route::put('budgets/{budgetId}', [BudgetController::class, 'update']);
    Route::delete('budgets/{budgetId}', [BudgetController::class, 'destroy']);
    Route::get('budgets/alerts/current-month', [BudgetController::class, 'alerts']);

    Route::get('savings-goals', [SavingsGoalController::class, 'index']);
    Route::post('savings-goals', [SavingsGoalController::class, 'store']);
    Route::put('savings-goals/{goalId}', [SavingsGoalController::class, 'update']);
    Route::delete('savings-goals/{goalId}', [SavingsGoalController::class, 'destroy']);
    Route::get('savings-goals/{goalId}/progress', [SavingsGoalController::class, 'progress']);

    Route::post('reconciliation/bulk', [ReconciliationController::class, 'bulkMarkReconciled']);
    Route::post('reconciliation/import-csv', [ReconciliationController::class, 'importBankCsv']);

    Route::get('reports/export', [ReportController::class, 'export']);
    Route::get('reports/compare', [ReportController::class, 'compare']);
    Route::get('reports/charts', [ReportController::class, 'charts']);
    Route::get('reports/calendar', [ReportController::class, 'calendar']);
    Route::get('reports/summary', [ReportController::class, 'weeklyOrMonthlySummary']);

    Route::get('profile', [ProfileController::class, 'show']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::post('profile/change-password', [ProfileController::class, 'changePassword']);

    Route::get('reminders', [ReminderController::class, 'index']);
    Route::post('reminders', [ReminderController::class, 'store']);
    Route::post('reminders/{reminderId}/read', [ReminderController::class, 'markAsRead']);
    Route::get('reminders/pending-tickets', [ReminderController::class, 'pendingTicketsSummary']);

    Route::get('backup/export', [BackupController::class, 'export']);
    Route::post('backup/restore', [BackupController::class, 'restore']);

    Route::get('dashboard/widgets', [DashboardWidgetController::class, 'index']);
    Route::post('dashboard/widgets', [DashboardWidgetController::class, 'upsert']);
});