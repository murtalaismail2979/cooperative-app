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

    public function index()
    {
        $expenses = Expense::with(['requester', 'approver', 'investment'])->latest()->paginate(15);
        $investments = \App\Models\Investment::where('status', 'active')->get();
        return view('admin.expenses.index', compact('expenses', 'investments'));
    }

    public function store(StoreExpenseRequest $request)
    {
        $this->expenseService->createExpense($request->validated(), true);

        return redirect()->route('admin.expenses.index')->with('success', 'Expense recorded successfully.');
    }

    public function pending()
    {
        $expenses = Expense::where('status', 'pending')->with(['requester', 'investment'])->latest()->paginate(15);
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
