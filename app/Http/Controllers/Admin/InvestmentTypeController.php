<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvestmentType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvestmentTypeController extends Controller
{
    public function index()
    {
        $types = InvestmentType::withCount('investments')->get();
        return view('admin.investment-types.index', compact('types'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:investment_types,name',
        ], [
            'name.unique' => 'This investment type already exists.',
        ]);

        $slug = Str::slug($request->name, '_');

        // Check if slug is unique
        if (InvestmentType::where('slug', $slug)->exists()) {
            return back()->withErrors(['name' => 'An investment type with a similar name already exists.'])->withInput();
        }

        InvestmentType::create([
            'name' => $request->name,
            'slug' => $slug,
        ]);

        return redirect()->route('admin.investment-types.index')->with('success', 'Investment type created successfully.');
    }

    public function destroy(InvestmentType $investmentType)
    {
        if ($investmentType->investments()->exists()) {
            return back()->with('error', 'Cannot delete this investment type because it is associated with active or completed investments.');
        }

        $investmentType->delete();

        return redirect()->route('admin.investment-types.index')->with('success', 'Investment type deleted successfully.');
    }
}
