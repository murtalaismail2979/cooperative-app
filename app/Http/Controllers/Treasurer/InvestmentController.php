<?php

namespace App\Http\Controllers\Treasurer;

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
        $year = $request->input('year');
        $month = $request->input('month');
        $type = $request->input('type');

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

        if (!empty($year)) {
            $query->whereYear('start_date', $year);
        }

        if (!empty($month)) {
            $query->whereMonth('start_date', $month);
        }

        if (!empty($type)) {
            $query->where(function($q) use ($type) {
                $slugType = strtolower(str_replace([' ', '&'], ['_', 'and'], trim($type)));
                $q->where('type', $type)
                  ->orWhere('type', $slugType)
                  ->orWhereHas('investmentType', function($itQuery) use ($type, $slugType) {
                      $itQuery->where('slug', $type)
                              ->orWhere('slug', $slugType)
                              ->orWhere('name', 'like', "%{$type}%")
                              ->orWhere('id', $type);
                  });
                if (is_numeric($type)) {
                    $q->orWhere('type', (int)$type);
                }
            });
        }

        $yearsFromDb = Investment::whereNotNull('start_date')
            ->get()
            ->map(fn($inv) => (int) $inv->start_date->format('Y'))
            ->unique()
            ->toArray();

        $availableYears = array_unique(array_merge($yearsFromDb, range((int)date('Y'), 2020)));
        rsort($availableYears);

        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        $dbTypes = InvestmentType::all();
        if ($dbTypes->isEmpty()) {
            $investmentTypes = collect([
                (object) ['slug' => 'buying_selling_goods', 'name' => 'Buying & Selling Goods'],
                (object) ['slug' => 'agriculture', 'name' => 'Agriculture'],
                (object) ['slug' => 'financing', 'name' => 'Financing'],
            ]);
        } else {
            $investmentTypes = $dbTypes->map(fn($t) => (object) ['slug' => $t->slug ?? $t->name, 'name' => $t->name]);
        }

        $investments = $query->latest()->paginate(15)->appends($request->only(['search', 'year', 'month', 'type']));
        return view('treasurer.investments.index', compact('investments', 'search', 'year', 'month', 'type', 'availableYears', 'months', 'investmentTypes'));
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
        return view('treasurer.investments.create', compact('investmentTypes'));
    }

    public function store(StoreInvestmentRequest $request)
    {
        $this->investmentService->createInvestment($request->validated());

        return redirect()->route('treasurer.investments.index')->with('success', 'Investment created.');
    }

    public function show(Investment $investment)
    {
        $investment->load(['returns.recorder', 'creator', 'expenses.requester']);
        return view('treasurer.investments.show', compact('investment'));
    }

    public function addReturn(Request $request, Investment $investment)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
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

        return redirect()->route('treasurer.investments.show', $investment)->with('success', 'Investment return deleted successfully.');
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
        return view('treasurer.investments.edit', compact('investment', 'investmentTypes'));
    }

    public function update(Request $request, Investment $investment)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'capital_amount' => 'required|numeric|min:0',
            'quantity' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,completed',
            'description' => 'nullable|string',
        ]);

        $this->investmentService->updateInvestment($investment, $validated);

        return redirect()->route('treasurer.investments.show', $investment)->with('success', 'Investment details updated successfully.');
    }

    public function destroy(Investment $investment)
    {
        $this->investmentService->deleteInvestment($investment);
        return redirect()->route('treasurer.investments.index')->with('success', 'Investment and associated returns/expenses deleted successfully.');
    }

    public function editReturn(Investment $investment, \App\Models\InvestmentReturn $return)
    {
        return view('treasurer.investments.returns.edit', compact('investment', 'return'));
    }

    public function updateReturn(Request $request, Investment $investment, \App\Models\InvestmentReturn $return)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'return_date' => 'required|date',
            'description' => 'nullable|string',
        ]);

        $this->investmentService->updateReturn($return, $validated['amount'], $validated['return_date'], $validated['description'] ?? null);

        return redirect()->route('treasurer.investments.show', $investment)->with('success', 'Investment return updated successfully.');
    }
}
