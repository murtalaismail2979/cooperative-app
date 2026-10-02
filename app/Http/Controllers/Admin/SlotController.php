<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slot;
use Illuminate\Http\Request;

class SlotController extends Controller
{
    public function index()
    {
        $slots = Slot::orderBy('slot_number')->get();

        return view('admin.slots.index', compact('slots'));
    }

    public function update(Request $request, Slot $slot)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'is_active' => 'required|boolean',
        ]);

        $slot->update($validated);

        return redirect()->route('admin.slots.index')
            ->with('success', "Slot {$slot->slot_number} updated successfully.");
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'slot_number' => 'required|integer|min:1|unique:slots,slot_number',
            'amount' => 'required|numeric|min:0',
            'is_active' => 'required|boolean',
        ]);

        $slot = Slot::create($validated);

        return redirect()->route('admin.slots.index')
            ->with('success', "Slot {$slot->slot_number} added successfully.");
    }
}