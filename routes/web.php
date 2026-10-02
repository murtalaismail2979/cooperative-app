<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard redirect based on role
    Route::get('/dashboard', function () {
        $user = auth()->user();
        if (in_array($user->role, ['admin', 'chairman', 'secretary', 'treasurer'])) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('member.dashboard');
    })->name('dashboard');

    // Profile (shared by all roles)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Get member active slots (shared by Admin/Treasurer)
    Route::get('/members/{user}/active-slots', function (App\Models\User $user) {
        return response()->json(
            $user->savingsSlots()->where('is_active', true)->get()
        );
    })->name('members.active-slots');

    // Get member savings info (shared by Admin/Treasurer)
    Route::get('/members/{user}/savings-info', function (App\Models\User $user) {
        $activeSlots = $user->savingsSlots()->where('is_active', true)->get();
        
        $totalSavings = \App\Models\MonthlySaving::where('user_id', $user->id)
            ->where('status', 'paid')
            ->sum('amount');

        $latestSaving = \App\Models\MonthlySaving::where('user_id', $user->id)
            ->selectRaw('month, SUM(amount) as total_amount, MAX(payment_date) as max_payment_date')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->first();

        $suggestedNextMonth = date('Y-m');
        if ($latestSaving && $latestSaving->month) {
            $suggestedNextMonth = \Carbon\Carbon::parse($latestSaving->month)->addMonth()->format('Y-m');
        }

        return response()->json([
            'active_slots_count' => $activeSlots->count(),
            'total_savings' => (float) $totalSavings,
            'formatted_total_savings' => '₦' . number_format($totalSavings, 2),
            'latest_month' => $latestSaving ? \Carbon\Carbon::parse($latestSaving->month)->format('F Y') : null,
            'latest_amount' => $latestSaving ? '₦' . number_format($latestSaving->total_amount, 2) : null,
            'latest_payment_date' => $latestSaving && $latestSaving->max_payment_date ? \Carbon\Carbon::parse($latestSaving->max_payment_date)->format('M d, Y') : null,
            'suggested_next_month' => $suggestedNextMonth,
        ]);
    })->name('members.savings-info');

    // ========== ADMIN ROUTES ==========
    Route::middleware(['role:admin,chairman,secretary,treasurer'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

        Route::get('/member-list', [App\Http\Controllers\Admin\UserController::class, 'memberList'])->name('members.view');
        Route::get('/member-list/export', [App\Http\Controllers\Admin\UserController::class, 'exportMembers'])->name('members.export');

        // User management is restricted to admins.
        Route::middleware(['role:admin'])->group(function () {
            Route::get('/members', [App\Http\Controllers\Admin\UserController::class, 'index'])->name('members.index');
            Route::get('/members/create', [App\Http\Controllers\Admin\UserController::class, 'create'])->name('members.create');
            Route::post('/members', [App\Http\Controllers\Admin\UserController::class, 'store'])->name('members.store');
            Route::get('/members/{member}/edit', [App\Http\Controllers\Admin\UserController::class, 'edit'])->name('members.edit');
            Route::get('/members/{member}/slots', [App\Http\Controllers\Admin\UserController::class, 'slots'])->name('members.slots');
            Route::put('/members/{member}/slots', [App\Http\Controllers\Admin\UserController::class, 'updateSlots'])->name('members.slots.update');
            Route::put('/members/{member}', [App\Http\Controllers\Admin\UserController::class, 'update'])->name('members.update');
            Route::patch('/members/{member}', [App\Http\Controllers\Admin\UserController::class, 'update']);
            Route::delete('/members/{member}', [App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('members.destroy');
            Route::get('/slots', [App\Http\Controllers\Admin\SlotController::class, 'index'])->name('slots.index');
            Route::post('/slots', [App\Http\Controllers\Admin\SlotController::class, 'store'])->name('slots.store');
            Route::put('/slots/{slot}', [App\Http\Controllers\Admin\SlotController::class, 'update'])->name('slots.update');

            // Investment Type Management
            Route::get('/investment-types', [App\Http\Controllers\Admin\InvestmentTypeController::class, 'index'])->name('investment-types.index');
            Route::post('/investment-types', [App\Http\Controllers\Admin\InvestmentTypeController::class, 'store'])->name('investment-types.store');
            Route::delete('/investment-types/{investmentType}', [App\Http\Controllers\Admin\InvestmentTypeController::class, 'destroy'])->name('investment-types.destroy');
        });

        // Savings Management
        Route::get('/savings', [App\Http\Controllers\Admin\SavingsController::class, 'index'])->name('savings.index');
        Route::get('/savings/create', [App\Http\Controllers\Admin\SavingsController::class, 'create'])->name('savings.create');
        Route::post('/savings', [App\Http\Controllers\Admin\SavingsController::class, 'store'])->name('savings.store');
        Route::get('/savings/history', [App\Http\Controllers\Admin\SavingsController::class, 'history'])->name('savings.history');
        Route::get('/savings/edit', [App\Http\Controllers\Admin\SavingsController::class, 'edit'])->name('savings.edit');
        Route::put('/savings/update', [App\Http\Controllers\Admin\SavingsController::class, 'update'])->name('savings.update');
        Route::delete('/savings/destroy', [App\Http\Controllers\Admin\SavingsController::class, 'destroy'])->name('savings.destroy');

        // Running Charges
        Route::get('/running-charges', [App\Http\Controllers\Admin\RunningChargeController::class, 'index'])->name('running-charges.index');
        Route::post('/running-charges', [App\Http\Controllers\Admin\RunningChargeController::class, 'store'])->name('running-charges.store');
        Route::post('/running-charges/update-interval', [App\Http\Controllers\Admin\RunningChargeController::class, 'updateInterval'])->name('running-charges.update-interval');
        Route::get('/running-charges/history', [App\Http\Controllers\Admin\RunningChargeController::class, 'history'])->name('running-charges.history');
        Route::get('/running-charges/{running_charge}/edit', [App\Http\Controllers\Admin\RunningChargeController::class, 'edit'])->name('running-charges.edit');
        Route::put('/running-charges/{running_charge}', [App\Http\Controllers\Admin\RunningChargeController::class, 'update'])->name('running-charges.update');
        Route::delete('/running-charges/{running_charge}', [App\Http\Controllers\Admin\RunningChargeController::class, 'destroy'])->name('running-charges.destroy');

        // Loan Management
        Route::resource('loans', App\Http\Controllers\Admin\LoanController::class);
        Route::post('/loans/{loan}/repayment', [App\Http\Controllers\Admin\LoanController::class, 'addRepayment'])->name('loans.repayment');
        Route::get('/loans/{loan}/repayments/{repayment}/edit', [App\Http\Controllers\Admin\LoanController::class, 'editRepayment'])->name('loans.repayments.edit');
        Route::put('/loans/{loan}/repayments/{repayment}', [App\Http\Controllers\Admin\LoanController::class, 'updateRepayment'])->name('loans.repayments.update');
        Route::delete('/loans/{loan}/repayments/{repayment}', [App\Http\Controllers\Admin\LoanController::class, 'destroyRepayment'])->name('loans.repayments.destroy');

        // Investment Management
        Route::resource('investments', App\Http\Controllers\Admin\InvestmentController::class);
        Route::post('/investments/{investment}/return', [App\Http\Controllers\Admin\InvestmentController::class, 'addReturn'])->name('investments.return');
        Route::delete('/investments/{investment}/returns/{return}', [App\Http\Controllers\Admin\InvestmentController::class, 'destroyReturn'])->name('investments.destroy_return');
        Route::get('/investments/{investment}/returns/{return}/edit', [App\Http\Controllers\Admin\InvestmentController::class, 'editReturn'])->name('investments.returns.edit');
        Route::put('/investments/{investment}/returns/{return}', [App\Http\Controllers\Admin\InvestmentController::class, 'updateReturn'])->name('investments.returns.update');



        // Expense Management (Admin approves)
        Route::get('/expenses', [App\Http\Controllers\Admin\ExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/expenses', [App\Http\Controllers\Admin\ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/expenses/pending', [App\Http\Controllers\Admin\ExpenseController::class, 'pending'])->name('expenses.pending');
        Route::post('/expenses/{expense}/approve', [App\Http\Controllers\Admin\ExpenseController::class, 'approve'])->name('expenses.approve');
        Route::post('/expenses/{expense}/reject', [App\Http\Controllers\Admin\ExpenseController::class, 'reject'])->name('expenses.reject');

        // Dividend Management
        Route::get('/dividends', [App\Http\Controllers\Admin\DividendController::class, 'index'])->name('dividends.index');
        Route::get('/dividends/create', [App\Http\Controllers\Admin\DividendController::class, 'create'])->name('dividends.create');
        Route::post('/dividends', [App\Http\Controllers\Admin\DividendController::class, 'store'])->name('dividends.store');
        Route::get('/dividends/{dividend}', [App\Http\Controllers\Admin\DividendController::class, 'show'])->name('dividends.show');
        Route::post('/dividends/{dividend}/pay', [App\Http\Controllers\Admin\DividendController::class, 'markAsPaid'])->name('dividends.pay');
        Route::delete('/dividends/{dividend}', [App\Http\Controllers\Admin\DividendController::class, 'destroy'])->name('dividends.destroy');

        // Dividend Loss Reconciliation & Financial Year Locking
        Route::get('/dividends-reconciliation', [App\Http\Controllers\Admin\DividendReconciliationController::class, 'index'])->name('dividends.reconciliation.index');
        Route::get('/dividends-reconciliation/{year}', [App\Http\Controllers\Admin\DividendReconciliationController::class, 'show'])->name('dividends.reconciliation.show');
        Route::post('/dividends-reconciliation/process/{year}', [App\Http\Controllers\Admin\DividendReconciliationController::class, 'process'])->name('dividends.reconciliation.process');
        Route::post('/dividends-reconciliation/recovery/{adjustment}', [App\Http\Controllers\Admin\DividendReconciliationController::class, 'recordRecovery'])->name('dividends.reconciliation.recovery');
        Route::post('/dividends-reconciliation/lock/{year}', [App\Http\Controllers\Admin\DividendReconciliationController::class, 'toggleYearLock'])->name('dividends.reconciliation.lock');

        // Reports
        Route::get('/reports/savings/export', [App\Http\Controllers\Admin\ReportController::class, 'exportSavings'])->name('reports.savings.export');
        Route::get('/reports/savings', [App\Http\Controllers\Admin\ReportController::class, 'savings'])->name('reports.savings');
        Route::get('/reports/loans/export', [App\Http\Controllers\Admin\ReportController::class, 'exportLoans'])->name('reports.loans.export');
        Route::get('/reports/loans', [App\Http\Controllers\Admin\ReportController::class, 'loans'])->name('reports.loans');
        Route::get('/reports/financial/export', [App\Http\Controllers\Admin\ReportController::class, 'exportFinancial'])->name('reports.financial.export');
        Route::get('/reports/financial', [App\Http\Controllers\Admin\ReportController::class, 'financial'])->name('reports.financial');
        Route::get('/reports/all/export', [App\Http\Controllers\Admin\ReportController::class, 'exportAll'])->name('reports.all.export');

        // Registration Fee Management
        Route::get('/registration-fees', [App\Http\Controllers\Admin\RegistrationFeeController::class, 'index'])->name('registration-fees.index');
        Route::get('/registration-fees/settings', [App\Http\Controllers\Admin\RegistrationFeeController::class, 'settings'])->name('registration-fees.settings');
        Route::post('/registration-fees/settings', [App\Http\Controllers\Admin\RegistrationFeeController::class, 'updateSettings'])->name('registration-fees.settings.update');
        Route::post('/registration-fees/payment', [App\Http\Controllers\Admin\RegistrationFeeController::class, 'storePayment'])->name('registration-fees.payment.store');
        Route::post('/registration-fees/payments/{payment}/cancel', [App\Http\Controllers\Admin\RegistrationFeeController::class, 'cancelPayment'])->name('registration-fees.payment.cancel');
        Route::post('/registration-fees/reconcile/{member}', [App\Http\Controllers\Admin\RegistrationFeeController::class, 'reconcile'])->name('registration-fees.reconcile');
        Route::get('/registration-fees/payments/{payment}/receipt', [App\Http\Controllers\Admin\RegistrationFeeController::class, 'receipt'])->name('registration-fees.receipt');
    });

    // ========== BATCH UPLOAD (ADMIN & TREASURER) ==========
    Route::middleware(['role:admin,treasurer'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/batch-upload', [App\Http\Controllers\Admin\BatchUploadController::class, 'index'])->name('batch-upload.index');
        Route::get('/batch-upload/template/{type}', [App\Http\Controllers\Admin\BatchUploadController::class, 'downloadTemplate'])->name('batch-upload.template');
        Route::post('/batch-upload/preview', [App\Http\Controllers\Admin\BatchUploadController::class, 'preview'])->name('batch-upload.preview');
        Route::post('/batch-upload/confirm', [App\Http\Controllers\Admin\BatchUploadController::class, 'confirmImport'])->name('batch-upload.confirm');
        Route::get('/batch-upload/error-report/{batchImport}', [App\Http\Controllers\Admin\BatchUploadController::class, 'downloadErrorReport'])->name('batch-upload.error-report');
    });

    // ========== TREASURER ROUTES ==========
    Route::middleware(['role:treasurer'])->prefix('treasurer')->name('treasurer.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Treasurer\DashboardController::class, 'index'])->name('dashboard');

        // Record Savings
        Route::get('/savings', [App\Http\Controllers\Treasurer\SavingsController::class, 'index'])->name('savings.index');
        Route::post('/savings', [App\Http\Controllers\Treasurer\SavingsController::class, 'store'])->name('savings.store');
        Route::get('/savings/history', [App\Http\Controllers\Treasurer\SavingsController::class, 'history'])->name('savings.history');
        Route::get('/savings/edit', [App\Http\Controllers\Treasurer\SavingsController::class, 'edit'])->name('savings.edit');
        Route::put('/savings/update', [App\Http\Controllers\Treasurer\SavingsController::class, 'update'])->name('savings.update');
        Route::delete('/savings/destroy', [App\Http\Controllers\Treasurer\SavingsController::class, 'destroy'])->name('savings.destroy');

        // Record/View Running Charges
        Route::get('/running-charges', [App\Http\Controllers\Treasurer\RunningChargeController::class, 'index'])->name('running-charges.index');
        Route::post('/running-charges', [App\Http\Controllers\Treasurer\RunningChargeController::class, 'store'])->name('running-charges.store');
        Route::get('/running-charges/history', [App\Http\Controllers\Treasurer\RunningChargeController::class, 'history'])->name('running-charges.history');
        Route::get('/running-charges/{running_charge}/edit', [App\Http\Controllers\Treasurer\RunningChargeController::class, 'edit'])->name('running-charges.edit');
        Route::put('/running-charges/{running_charge}', [App\Http\Controllers\Treasurer\RunningChargeController::class, 'update'])->name('running-charges.update');
        Route::delete('/running-charges/{running_charge}', [App\Http\Controllers\Treasurer\RunningChargeController::class, 'destroy'])->name('running-charges.destroy');

        // Record Loan Repayments
        Route::resource('loans', App\Http\Controllers\Treasurer\LoanController::class);
        Route::post('/loans/{loan}/repayment', [App\Http\Controllers\Treasurer\LoanController::class, 'addRepayment'])->name('loans.repayment');
        Route::get('/loans/{loan}/repayments/{repayment}/edit', [App\Http\Controllers\Treasurer\LoanController::class, 'editRepayment'])->name('loans.repayments.edit');
        Route::put('/loans/{loan}/repayments/{repayment}', [App\Http\Controllers\Treasurer\LoanController::class, 'updateRepayment'])->name('loans.repayments.update');
        Route::delete('/loans/{loan}/repayments/{repayment}', [App\Http\Controllers\Treasurer\LoanController::class, 'destroyRepayment'])->name('loans.repayments.destroy');

        // Create Expense Request
        Route::get('/expenses/create', [App\Http\Controllers\Treasurer\ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/expenses', [App\Http\Controllers\Treasurer\ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/expenses', [App\Http\Controllers\Treasurer\ExpenseController::class, 'index'])->name('expenses.index');

        // Record Investment Returns
        Route::resource('investments', App\Http\Controllers\Treasurer\InvestmentController::class);
        Route::post('/investments/{investment}/return', [App\Http\Controllers\Treasurer\InvestmentController::class, 'addReturn'])->name('investments.return');
        Route::delete('/investments/{investment}/returns/{return}', [App\Http\Controllers\Treasurer\InvestmentController::class, 'destroyReturn'])->name('investments.destroy_return');
        Route::get('/investments/{investment}/returns/{return}/edit', [App\Http\Controllers\Treasurer\InvestmentController::class, 'editReturn'])->name('investments.returns.edit');
        Route::put('/investments/{investment}/returns/{return}', [App\Http\Controllers\Treasurer\InvestmentController::class, 'updateReturn'])->name('investments.returns.update');

        // Registration Fee Management
        Route::get('/registration-fees', [App\Http\Controllers\Treasurer\RegistrationFeeController::class, 'index'])->name('registration-fees.index');
        Route::post('/registration-fees/payment', [App\Http\Controllers\Treasurer\RegistrationFeeController::class, 'storePayment'])->name('registration-fees.payment.store');
        Route::get('/registration-fees/payments/{payment}/receipt', [App\Http\Controllers\Treasurer\RegistrationFeeController::class, 'receipt'])->name('registration-fees.receipt');
    });

    // ========== MEMBER ROUTES ==========
    Route::middleware(['role:member'])->prefix('member')->name('member.')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Member\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/savings', [App\Http\Controllers\Member\DashboardController::class, 'savings'])->name('savings');
        Route::get('/loans', [App\Http\Controllers\Member\DashboardController::class, 'loans'])->name('loans');
        Route::get('/dividends', [App\Http\Controllers\Member\DashboardController::class, 'dividends'])->name('dividends');
        Route::get('/registration-fee', [App\Http\Controllers\Member\RegistrationFeeController::class, 'index'])->name('registration-fee.index');
        Route::get('/registration-fee/payments/{payment}/receipt', [App\Http\Controllers\Member\RegistrationFeeController::class, 'receipt'])->name('registration-fee.receipt');
    });
});

require __DIR__.'/auth.php';