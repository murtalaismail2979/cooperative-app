<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'cooperative_id',
        'name',
        'email',
        'password',
        'member_code',
        'registration_year',
        'registration_month',
        'phone',
        'address',
        'role',
        'is_active',
        'membership_status',
        'approved_at',
        'readmitted_at',
        'approved_by',
        'rejection_reason',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'approved_at' => 'datetime',
        'readmitted_at' => 'date',
    ];

    // Relationships
    public function cooperative()
    {
        return $this->belongsTo(Cooperative::class);
    }

    public function savingsSlots()
    {
        return $this->hasMany(SavingsSlot::class);
    }

    public function monthlySavings()
    {
        return $this->hasMany(MonthlySaving::class);
    }

    public function runningCharges()
    {
        return $this->hasMany(RunningCharge::class);
    }

    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    public function loanRepayments()
    {
        return $this->hasMany(LoanRepayment::class);
    }

    public function dividendPayouts()
    {
        return $this->hasMany(DividendPayout::class);
    }

    public function nextOfKin()
    {
        return $this->hasOne(NextOfKin::class);
    }

    public function slotHistories()
    {
        return $this->hasMany(MemberSlotHistory::class);
    }

    public function registrationFee()
    {
        return $this->hasOne(RegistrationFee::class);
    }

    public function registrationFeePayments()
    {
        return $this->hasMany(RegistrationFeePayment::class);
    }

    public function dividendAdjustments()
    {
        return $this->hasMany(DividendAdjustment::class);
    }

    public function dividendRecoveryPayments()
    {
        return $this->hasMany(DividendRecoveryPayment::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePending($query)
    {
        return $query->where('membership_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('membership_status', 'approved');
    }

    public function scopeRole($query, $role)
    {
        return $query->where('role', $role);
    }

    // Helper methods
    public function isPendingMembership(): bool
    {
        return $this->membership_status === 'pending';
    }

    public function isApprovedMembership(): bool
    {
        return $this->membership_status === 'approved' || (empty($this->membership_status) && $this->is_active);
    }

    public function isRejectedMembership(): bool
    {
        return $this->membership_status === 'rejected';
    }

    // Helper methods
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    public function isChairman(): bool
    {
        return $this->role === 'chairman';
    }

    public function isSecretary(): bool
    {
        return $this->role === 'secretary';
    }

    public function isTreasurer(): bool
    {
        return $this->role === 'treasurer';
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    public function isManagementRole(): bool
    {
        return in_array($this->role, ['admin', 'super_admin', 'chairman', 'secretary', 'treasurer']);
    }

    public function isAdminOrTreasurer(): bool
    {
        return in_array($this->role, ['admin', 'super_admin', 'treasurer']);
    }

    public function getRegistrationMonthNameAttribute(): ?string
    {
        if (!$this->registration_month) {
            return null;
        }
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        return $months[(int)$this->registration_month] ?? null;
    }

    public function getRegistrationMonthYearAttribute(): string
    {
        if ($this->registration_month_name && $this->registration_year) {
            return $this->registration_month_name . ' ' . $this->registration_year;
        }
        if ($this->registration_year) {
            return (string) $this->registration_year;
        }
        return 'N/A';
    }

    /**
     * Auto-generate member code in format: PREFIX/YY/XXXX
     */
    public static function generateMemberCode(?int $registrationYear = null, ?int $cooperativeId = null): string
    {
        $year = $registrationYear ? substr((string) $registrationYear, -2) : date('y');
        
        $prefix = 'YLDA';
        if ($cooperativeId) {
            $coop = Cooperative::find($cooperativeId);
            if ($coop && !empty($coop->slug)) {
                $prefix = strtoupper($coop->slug);
            }
        }

        $query = self::where('member_code', 'like', "{$prefix}/%/%");
        if ($cooperativeId) {
            $query->where('cooperative_id', $cooperativeId);
        }

        $lastMember = $query->orderBy('id', 'desc')->first();

        if ($lastMember && $lastMember->member_code) {
            $parts = explode('/', $lastMember->member_code);
            $lastNumber = (int) end($parts);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('%s/%s/%04d', $prefix, $year, $newNumber);
    }

    /**
     * Get the effective re-admission date if member was re-admitted.
     */
    public function getReadmissionDate(): ?\Carbon\Carbon
    {
        if (!empty($this->readmitted_at)) {
            return \Carbon\Carbon::parse($this->readmitted_at);
        }

        $history = $this->slotHistories
            ? $this->slotHistories->filter(fn($h) => str_starts_with($h->reason ?? '', 'Re-admitted'))->sortByDesc('created_at')->first()
            : $this->slotHistories()->where('reason', 'like', 'Re-admitted%')->latest('created_at')->first();

        return $history ? \Carbon\Carbon::parse($history->created_at) : null;
    }

    /**
     * Get the start of the effective savings contribution period (re-admission month or null).
     */
    public function getEffectiveSavingsStartDate(): ?\Carbon\Carbon
    {
        if ($date = $this->getReadmissionDate()) {
            return $date->copy()->startOfMonth();
        }

        return null;
    }
}