<?php

namespace App\Services;

use App\Models\Expense;

class ExpenseService
{
    /**
     * Create a new expense request.
     */
    public function createExpenseRequest(array $data): Expense
    {
        return $this->createExpense($data, false);
    }

    /**
     * Create a new expense.
     */
    public function createExpense(array $data, bool $approved = false): Expense
    {
        $data['requested_by'] = auth()->id();
        if ($approved) {
            $data['status'] = 'approved';
            $data['approved_by'] = auth()->id();
            $data['approved_at'] = now();
        } else {
            $data['status'] = 'pending';
        }

        return Expense::create($data);
    }

    /**
     * Approve a pending expense.
     */
    public function approveExpense(Expense $expense): void
    {
        $expense->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject a pending expense.
     */
    public function rejectExpense(Expense $expense): void
    {
        $expense->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Get expense summary stats.
     */
    public function getExpenseStats(?int $cooperativeId = null): array
    {
        $query = Expense::query();
        if ($cooperativeId) {
            $query->whereHas('requestedBy', function ($q) use ($cooperativeId) {
                $q->where('cooperative_id', $cooperativeId);
            });
        }

        return [
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'totalApproved' => (clone $query)->where('status', 'approved')->sum('amount'),
            'totalRejected' => (clone $query)->where('status', 'rejected')->sum('amount'),
        ];
    }
}