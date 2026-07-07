<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvestmentRequest;
use App\Models\Investment;
use App\Models\InvestmentType;
use App\Services\InvestmentService;
use Illuminate\Http\Request;

class InvestmentController extends Controller
{
    protected $investmentService;

    public function __construct(InvestmentService $investmentService)
    {
        $this->investmentService = $investmentService;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = Investment::with(['creator', 'investmentType']);

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('capital_amount', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhereHas('investmentType', function($itQuery) use ($search) {
                      $itQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $investments = $query->latest()->paginate(15)->appends($request->only('search'));
        return view('admin.investments.index', compact('investments', 'search'));
    }

    public function create()
    {
        $investmentTypes = InvestmentType::all();
        if ($investmentTypes->isEmpty()) {
            $investmentTypes = collect([
                (object) ['slug' => 'buying_selling_goods', 'name' => 'Buying & Selling Goods'],
                (object) ['slug' => 'agriculture', 'name' => 'Agriculture'],
                (object) ['slug' => 'financing', 'name' => 'Financing'],
            ]);
        }
        return view('admin.investments.create', compact('investmentTypes'));
    }

    public function store(StoreInvestmentRequest $request)
    {
        $this->investmentService->createInvestment($request->validated());

        return redirect()->route('admin.investments.index')->with('success', 'Investment created successfully.');
    }

    public function show(Investment $investment)
    {
        $investment->load(['returns.recorder', 'creator', 'expenses.requester']);
        return view('admin.investments.show', compact('investment'));
    }

    public function addReturn(Request $request, Investment $investment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'return_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $this->investmentService->recordReturn(
            $investment,
            $validated['amount'],
            $validated['return_date'],
            $validated['description'] ?? null
        );

        return redirect()->back()->with('success', 'Return recorded.');
    }

    public function destroyReturn(Investment $investment, \App\Models\InvestmentReturn $return)
    {
        $this->investmentService->deleteReturn($return);

        return redirect()->route('admin.investments.show', $investment)->with('success', 'Investment return deleted successfully.');
    }

    public function edit(Investment $investment)
    {
        $investmentTypes = InvestmentType::all();
        if ($investmentTypes->isEmpty()) {
            $investmentTypes = collect([
                (object) ['slug' => 'buying_selling_goods', 'name' => 'Buying & Selling Goods'],
                (object) ['slug' => 'agriculture', 'name' => 'Agriculture'],
                (object) ['slug' => 'financing', 'name' => 'Financing'],
            ]);
        }
        return view('admin.investments.edit', compact('investment', 'investmentTypes'));
    }

    public function update(Request $request, Investment $investment)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'capital_amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,completed',
            'description' => 'nullable|string',
        ]);

        $this->investmentService->updateInvestment($investment, $validated);

        return redirect()->route('admin.investments.show', $investment)->with('success', 'Investment details updated successfully.');
    }

    public function destroy(Investment $investment)
    {
        $this->investmentService->deleteInvestment($investment);
        return redirect()->route('admin.investments.index')->with('success', 'Investment and associated returns/expenses deleted successfully.');
    }

    public function editReturn(Investment $investment, \App\Models\InvestmentReturn $return)
    {
        return view('admin.investments.returns.edit', compact('investment', 'return'));
    }

    public function updateReturn(Request $request, Investment $investment, \App\Models\InvestmentReturn $return)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'return_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $this->investmentService->updateReturn($return, $validated['amount'], $validated['return_date'], $validated['description'] ?? null);

        return redirect()->route('admin.investments.show', $investment)->with('success', 'Investment return updated successfully.');
    }
}

