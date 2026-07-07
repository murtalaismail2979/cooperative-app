<?php

namespace App\Http\Controllers\Treasurer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;
use App\Services\ExpenseService;

class ExpenseController extends Controller
{
    protected $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }

    public function index()
    {
        $expenses = Expense::where('requested_by', auth()->id())->with('investment')->latest()->paginate(15);
        return view('treasurer.expenses.index', compact('expenses'));
    }

    public function create()
    {
        $investments = \App\Models\Investment::where('status', 'active')->get();
        return view('treasurer.expenses.create', compact('investments'));
    }

    public function store(StoreExpenseRequest $request)
    {
        $this->expenseService->createExpenseRequest($request->validated());

        return redirect()->route('treasurer.expenses.index')->with('success', 'Expense request submitted for approval.');
    }
}
