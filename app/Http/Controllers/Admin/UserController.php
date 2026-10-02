<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SavingsSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $regYear = $request->input('reg_year');
        $slots = $request->input('slots');
        $status = $request->input('status');
        $roleFilter = $request->input('role');

        $query = User::with('savingsSlots');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($regYear) {
            $query->where('registration_year', $regYear);
        }

        if ($slots) {
            $query->whereHas('savingsSlots', function ($q) {
                $q->where('is_active', true);
            }, '=', $slots);
        }

        if ($status) {
            $query->where('is_active', $status === 'active');
        }

        if ($roleFilter) {
            $query->where('role', $roleFilter);
        }

        if ($roleFilter === 'member' || !$roleFilter) {
            $query->orderByRaw('CASE WHEN member_code IS NULL OR member_code = "" THEN 1 ELSE 0 END, member_code ASC, name ASC');
        } else {
            $query->orderBy('name');
        }

        $members = $query->paginate(15)->withQueryString();

        $registrationYears = User::whereNotNull('registration_year')
            ->select('registration_year')
            ->distinct()
            ->orderBy('registration_year', 'desc')
            ->pluck('registration_year')
            ->toArray();

        return view('admin.members.index', compact(
            'members', 'search', 'regYear', 'slots', 'status', 'roleFilter', 'registrationYears'
        ));
    }

    public function memberList(Request $request)
    {
        $search = $request->input('search');
        $regYear = $request->input('reg_year');
        $slots = $request->input('slots');
        $status = $request->input('status');

        $query = User::with('savingsSlots')->where('role', 'member');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($regYear) {
            $query->where('registration_year', $regYear);
        }

        if ($slots) {
            $query->whereHas('savingsSlots', function ($q) {
                $q->where('is_active', true);
            }, '=', $slots);
        }

        if ($status) {
            $query->where('is_active', $status === 'active');
        }

        $query->orderByRaw('CASE WHEN member_code IS NULL OR member_code = "" THEN 1 ELSE 0 END, member_code ASC, name ASC');

        $members = $query->paginate(15)->withQueryString();
        $registrationYears = User::where('role', 'member')
            ->whereNotNull('registration_year')
            ->pluck('registration_year')
            ->unique()
            ->sortDesc()
            ->values()
            ->toArray();

        return view('admin.members.index', [
            'members' => $members,
            'search' => $search,
            'regYear' => $regYear,
            'slots' => $slots,
            'status' => $status,
            'roleFilter' => 'member',
            'registrationYears' => $registrationYears,
            'readOnly' => true,
        ]);
    }

    public function exportMembers(Request $request)
    {
        $query = User::with(['savingsSlots', 'nextOfKin'])->where('role', 'member');
        $search = $request->input('search');
        $regYear = $request->input('reg_year');
        $slots = $request->input('slots');
        $status = $request->input('status');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('member_code', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($regYear) {
            $query->where('registration_year', $regYear);
        }

        if ($slots) {
            $query->whereHas('savingsSlots', function ($q) {
                $q->where('is_active', true);
            }, '=', $slots);
        }

        if ($status) {
            $query->where('is_active', $status === 'active');
        }

        $members = $query->orderByRaw('CASE WHEN member_code IS NULL OR member_code = "" THEN 1 ELSE 0 END, member_code ASC, name ASC')->get();
        $headers = ['Member Code', 'Full Name', 'Email', 'Phone', 'Registration Year', 'Active Slots', 'Status', 'Next of Kin Name', 'Next of Kin Phone', 'Next of Kin Relationship', 'Next of Kin Address'];

        return response()->streamDownload(function () use ($members, $headers) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $headers);

            foreach ($members as $member) {
                fputcsv($file, [
                    $member->member_code,
                    $member->name,
                    $member->email,
                    $member->phone,
                    $member->registration_year,
                    $member->savingsSlots->where('is_active', true)->count(),
                    $member->is_active ? 'Active' : 'Inactive',
                    $member->nextOfKin?->name,
                    $member->nextOfKin?->phone,
                    $member->nextOfKin?->relationship,
                    $member->nextOfKin?->address,
                ]);
            }

            fclose($file);
        }, 'members_' . date('Y-m-d') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function create()
    {
        $defaultRegistrationFee = app(\App\Services\RegistrationFeeService::class)->getCurrentFeeAmount();
        return view('admin.members.create', compact('defaultRegistrationFee'));
    }

    public function store(Request $request)
    {
        $request->merge(['role' => $request->input('role', 'member')]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:member,chairman,secretary,treasurer,admin',
            'slots' => 'required_if:role,member|nullable|integer|min:1|max:10',
            'registration_year' => 'nullable|integer|min:2000|max:2100',
            'registration_month' => 'nullable|integer|min:1|max:12',
            'registration_fee' => 'nullable|numeric|min:0.01',
            'payment_method' => 'nullable|string|in:Pending,Cash,Bank Transfer,Cheque,Online,Other',
            'reference_number' => 'nullable|string|max:255',
            'nok_name' => 'required_if:role,member|nullable|string|max:255',
            'nok_phone' => 'required_if:role,member|nullable|string|max:20',
            'nok_relationship' => 'required_if:role,member|nullable|string|max:255',
            'nok_address' => 'nullable|string|max:500',
        ]);

        $regYear = $validated['registration_year'] ?? (int) date('Y');
        $regMonth = isset($validated['registration_month']) && $validated['registration_month'] !== null ? (int) $validated['registration_month'] : (int) date('n');

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $regYear, $regMonth) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'member_code' => $validated['role'] === 'member' ? User::generateMemberCode($regYear) : null,
                'registration_year' => $validated['role'] === 'member' ? $regYear : null,
                'registration_month' => $validated['role'] === 'member' ? $regMonth : null,
                'role' => $validated['role'],
                'is_active' => true,
            ]);

            if ($validated['role'] === 'member') {
                // Create savings slots
                for ($i = 1; $i <= $validated['slots']; $i++) {
                    $user->savingsSlots()->create([
                        'slot_number' => $i,
                        'is_active' => true,
                    ]);
                }

                // Record initial slot creation history
                $user->slotHistories()->create([
                    'previous_slots' => 0,
                    'current_slots' => $validated['slots'],
                    'changed_by' => auth()->id(),
                    'reason' => 'Initial registration',
                ]);

                // Create Next of Kin
                $user->nextOfKin()->create([
                    'name' => $validated['nok_name'],
                    'phone' => $validated['nok_phone'],
                    'relationship' => $validated['nok_relationship'],
                    'address' => $validated['nok_address'] ?? null,
                ]);

                // Create Registration Fee Obligation
                $regService = app(\App\Services\RegistrationFeeService::class);
                $feeAmount = isset($validated['registration_fee']) && $validated['registration_fee'] !== null
                    ? (float) $validated['registration_fee']
                    : $regService->getCurrentFeeAmount();
                $paymentMethod = !empty($validated['payment_method']) ? $validated['payment_method'] : 'Cash';
                $referenceNumber = $validated['reference_number'] ?? null;

                $fee = $regService->createObligationForMember($user, $feeAmount);

                // Only record payment immediately if payment method is not Pending
                if ($paymentMethod !== 'Pending') {
                    $regService->recordPayment(
                        $fee,
                        $feeAmount,
                        date('Y-m-d'),
                        $paymentMethod,
                        $referenceNumber,
                        auth()->id()
                    );
                }
            }
        });

        return redirect()->route('admin.members.index')->with('success', 'User added successfully.');
    }

    public function edit(User $member)
    {
        $member->load(['nextOfKin', 'slotHistories.changedBy']);
        return view('admin.members.edit', compact('member'));
    }

    public function slots(User $member)
    {
        abort_unless($member->isMember(), 404, 'Only member accounts have savings slots.');

        $member->load(['savingsSlots', 'slotHistories.changedBy']);
        return view('admin.members.slots', compact('member'));
    }

    public function updateSlots(Request $request, User $member)
    {
        abort_unless($member->isMember(), 404, 'Only member accounts have savings slots.');

        $validated = $request->validate([
            'slots' => 'required|integer|min:1|max:10',
            'slot_change_date' => 'nullable|date',
        ]);

        $oldSlotsCount = $member->savingsSlots()->where('is_active', true)->count();
        $newSlotsCount = (int) $validated['slots'];

        \Illuminate\Support\Facades\DB::transaction(function () use ($member, $oldSlotsCount, $newSlotsCount, $validated) {
            if ($newSlotsCount > $oldSlotsCount) {
                for ($slotNumber = 1; $slotNumber <= $newSlotsCount; $slotNumber++) {
                    $slot = $member->savingsSlots()->where('slot_number', $slotNumber)->first();
                    if ($slot) {
                        $slot->update(['is_active' => true]);
                    } else {
                        $member->savingsSlots()->create([
                            'slot_number' => $slotNumber,
                            'is_active' => true,
                        ]);
                    }
                }
            } elseif ($newSlotsCount < $oldSlotsCount) {
                for ($slotNumber = $newSlotsCount + 1; $slotNumber <= 10; $slotNumber++) {
                    $member->savingsSlots()
                        ->where('slot_number', $slotNumber)
                        ->where('is_active', true)
                        ->update(['is_active' => false]);
                }
            }

            if ($newSlotsCount !== $oldSlotsCount) {
                $effectiveDate = !empty($validated['slot_change_date'])
                    ? \Carbon\Carbon::parse($validated['slot_change_date'])->startOfDay()
                    : now();

                $history = new \App\Models\MemberSlotHistory([
                    'previous_slots' => $oldSlotsCount,
                    'current_slots' => $newSlotsCount,
                    'changed_by' => auth()->id(),
                    'reason' => 'Updated by Admin',
                ]);
                $history->created_at = $effectiveDate;
                $history->updated_at = $effectiveDate;
                $member->slotHistories()->save($history);
            }
        });

        return redirect()->route('admin.members.slots', $member)
            ->with('success', 'Member slots updated successfully.');
    }

    public function update(Request $request, User $member)
    {
        $request->merge(['role' => $request->input('role', $member->role)]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $member->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'role' => 'required|string|in:member,chairman,secretary,treasurer,admin',
            'registration_year' => 'nullable|integer|min:2000|max:2100',
            'registration_month' => 'nullable|integer|min:1|max:12',
            'is_active' => 'boolean',
            'slots' => 'required_if:role,member|nullable|integer|min:1|max:10',
            'slot_change_date' => 'nullable|date',
            'nok_name' => 'required_if:role,member|nullable|string|max:255',
            'nok_phone' => 'required_if:role,member|nullable|string|max:20',
            'nok_relationship' => 'required_if:role,member|nullable|string|max:255',
            'nok_address' => 'nullable|string|max:500',
        ]);

        $regYear = $validated['registration_year'] ?? $member->registration_year ?? (int) date('Y');
        $regMonth = isset($validated['registration_month']) && $validated['registration_month'] !== null ? (int) $validated['registration_month'] : ($member->registration_month ?? (int) date('n'));

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $member, $regYear, $regMonth) {
            $newRole = $validated['role'];

            $memberCode = $member->member_code;
            if ($newRole === 'member') {
                if (!$memberCode) {
                    $memberCode = User::generateMemberCode($regYear);
                } elseif (preg_match('/^YLDA\/\d{2}\/(\d{4})$/', $memberCode, $matches)) {
                    $yearStr = substr((string) $regYear, -2);
                    $memberCode = "YLDA/{$yearStr}/{$matches[1]}";
                }
            } else {
                $memberCode = null;
            }

            $member->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'registration_year' => $newRole === 'member' ? $regYear : null,
                'registration_month' => $newRole === 'member' ? $regMonth : null,
                'member_code' => $memberCode,
                'role' => $newRole,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            if ($newRole === 'member') {
                // Handle slots changes
                $newSlotsCount = (int) $validated['slots'];
                $oldSlotsCount = $member->savingsSlots()->where('is_active', true)->count();

                if ($newSlotsCount !== $oldSlotsCount) {
                    if (!auth()->user() || auth()->user()->role !== 'admin') {
                        abort(403, 'Only admins can change slots.');
                    }
                    // If increasing slots
                    if ($newSlotsCount > $oldSlotsCount) {
                        for ($i = 1; $i <= $newSlotsCount; $i++) {
                            $slot = $member->savingsSlots()->where('slot_number', $i)->first();
                            if ($slot) {
                                if (!$slot->is_active) {
                                    $slot->update(['is_active' => true]);
                                }
                            } else {
                                $member->savingsSlots()->create([
                                    'slot_number' => $i,
                                    'is_active' => true,
                                ]);
                            }
                        }
                    } 
                    // If decreasing slots
                    else {
                        for ($i = $newSlotsCount + 1; $i <= 10; $i++) {
                            $slot = $member->savingsSlots()->where('slot_number', $i)->first();
                            if ($slot && $slot->is_active) {
                                $slot->update(['is_active' => false]);
                            }
                        }
                    }

                    // Log the history of slot changes
                    $effectiveDate = !empty($validated['slot_change_date']) ? \Carbon\Carbon::parse($validated['slot_change_date'])->startOfDay() : now();
                    
                    $history = new \App\Models\MemberSlotHistory([
                        'previous_slots' => $oldSlotsCount,
                        'current_slots' => $newSlotsCount,
                        'changed_by' => auth()->id(),
                        'reason' => 'Updated by Admin',
                    ]);
                    $history->created_at = $effectiveDate;
                    $history->updated_at = $effectiveDate;
                    $member->slotHistories()->save($history);
                }

                $member->nextOfKin()->updateOrCreate(
                    ['user_id' => $member->id],
                    [
                        'name' => $validated['nok_name'],
                        'phone' => $validated['nok_phone'],
                        'relationship' => $validated['nok_relationship'],
                        'address' => $validated['nok_address'] ?? null,
                    ]
                );
            } else {
                // If the user is changed to treasurer/admin, we deactivate their active slots
                $member->savingsSlots()->update(['is_active' => false]);
            }
        });

        return redirect()->route('admin.members.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $member)
    {
        $member->update(['is_active' => false]);
        return redirect()->route('admin.members.index')->with('success', 'User deactivated successfully.');
    }
}