<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Services\ExpenseService;

use App\Http\Requests\StoreExpenseRequest;

class ExpenseController extends Controller
{
    protected $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $cooperativeId = $user->isSuperAdmin() ? $request->get('cooperative_id') : $user->cooperative_id;

        $query = Expense::with(['requester', 'approver', 'investment']);
        if ($cooperativeId) {
            $query->whereHas('requestedBy', fn($u) => $u->where('cooperative_id', $cooperativeId));
        }
        $expenses = $query->latest()->paginate(15);

        $invQuery = \App\Models\Investment::where('status', 'active');
        if ($cooperativeId) {
            $invQuery->whereHas('createdBy', fn($u) => $u->where('cooperative_id', $cooperativeId));
        }
        $investments = $invQuery->get();

        return view('admin.expenses.index', compact('expenses', 'investments'));
    }

    public function store(StoreExpenseRequest $request)
    {
        $this->expenseService->createExpense($request->validated(), true);

        return redirect()->route('admin.expenses.index')->with('success', 'Expense recorded successfully.');
    }

    public function pending(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $cooperativeId = $user->isSuperAdmin() ? $request->get('cooperative_id') : $user->cooperative_id;

        $query = Expense::where('status', 'pending')->with(['requester', 'investment']);
        if ($cooperativeId) {
            $query->whereHas('requestedBy', fn($u) => $u->where('cooperative_id', $cooperativeId));
        }
        $expenses = $query->latest()->paginate(15);

        return view('admin.expenses.pending', compact('expenses'));
    }

    public function approve(Expense $expense)
    {
        $this->expenseService->approveExpense($expense);
        return back()->with('success', 'Expense approved successfully.');
    }

    public function reject(Expense $expense)
    {
        $this->expenseService->rejectExpense($expense);
        return back()->with('success', 'Expense rejected.');
    }
}
