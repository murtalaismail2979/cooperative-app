<?php

namespace App\Services;

use App\Models\BatchImport;
use App\Models\Loan;
use App\Models\MonthlySaving;
use App\Models\RunningCharge;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BatchImportService
{
    /**
     * Generate downloadable CSV template with sample data.
     */
    public function generateTemplate(string $type, ?array $monthFilter = null): string
    {
        $headers = [];
        $sampleData = [];

        switch ($type) {
            case 'members':
                $headers = [
                    'Full Name',
                    'Email',
                    'Phone Number',
                    'Contact Address',
                    'Registration Year',
                    'Role (member/chairman/secretary/treasurer/admin)',
                    'Savings Slots (1-10)',
                    'Next of Kin Name',
                    'Next of Kin Phone',
                    'Next of Kin Relationship',
                    'Next of Kin Address',
                ];
                $sampleData = [
                    [
                        'John Doe',
                        'john.doe@example.com',
                        '08012345678',
                        '123 Cooperative Street, Lagos',
                        '2026',
                        'member',
                        '2',
                        'Jane Doe',
                        '08087654321',
                        'Spouse',
                        '123 Cooperative Street, Lagos',
                    ],
                ];
                break;

            case 'savings':
                $headers = [
                    'Member Code',
                    'Full Name',
                    'Current Slot No',
                    'Month (YYYY-MM)',
                    'Amount (NGN)',
                    'Payment Date (YYYY-MM-DD)',
                ];

                $members = User::where('role', 'member')
                    ->with(['savingsSlots' => function ($query) {
                        $query->orderBy('slot_number');
                    }])
                    ->orderByRaw('CASE WHEN member_code IS NULL OR member_code = "" THEN 1 ELSE 0 END, member_code ASC, name ASC')
                    ->get();

                if ($members->isEmpty()) {
                    $sampleData = [];
                    break;
                }

                $currentMonth = now()->format('Y-m');
                foreach ($members as $member) {
                    $activeSlots = $member->savingsSlots->where('is_active', true);
                    $activeCount = $activeSlots->count();
                    $maxActiveSlot = $activeSlots->max('slot_number');
                    $slotNo = $maxActiveSlot ? $maxActiveSlot : ($activeCount > 0 ? $activeCount : 1);

                    $configuredAmount = \App\Models\Slot::where('slot_number', $slotNo)->value('amount');
                    $slotAmount = $configuredAmount 
                        ? number_format((float)$configuredAmount, 2, '.', '')
                        : number_format($activeCount * 2000.00, 2, '.', '');

                    $sampleData[] = [
                        $member->member_code ?? '',
                        $member->name ?? '',
                        (string) $slotNo,
                        $currentMonth,
                        $slotAmount,
                        '',
                    ];
                }
                break;

            case 'running_charges':
                $headers = [
                    'Member Code',
                    'Full Name',
                    'Month (YYYY-MM)',
                    'Amount (NGN)',
                    'Payment Date (YYYY-MM-DD)',
                ];
                $allowedMonths = [
                    2021 => range(10, 12),
                    2022 => range(1, 4),
                    2023 => range(1, 12),
                    2024 => range(1, 12),
                    2025 => range(1, 12),
                    2026 => range(1, 8),
                ];

                if ($monthFilter !== null) {
                    $allowedMonths = array_intersect_key(
                        $allowedMonths,
                        array_flip(array_keys($monthFilter))
                    );
                    foreach ($allowedMonths as $year => $months) {
                        $allowedMonths[$year] = array_values(array_intersect($months, $monthFilter[$year] ?? []));
                    }
                }

                $members = User::where('role', 'member')
                    ->whereNotNull('member_code')
                    ->where('member_code', '<>', '')
                    ->orderBy('member_code')
                    ->get(['member_code', 'name']);

                foreach ($members as $member) {
                    foreach ($allowedMonths as $year => $months) {
                        foreach ($months as $month) {
                            $monthValue = sprintf('%d-%02d', $year, $month);
                            $sampleData[] = [
                                $member->member_code,
                                $member->name,
                                $monthValue,
                                $year <= 2021 ? '100.00' : ($year <= 2023 ? '300.00' : '500.00'),
                                $monthValue . '-05',
                            ];
                        }
                    }
                }
                break;

            case 'loans':
                $headers = [
                    'Member Code',
                    'Principal Amount (NGN)',
                    'Profit Rate (%)',
                    'Duration (Months)',
                    'Date Granted (YYYY-MM-DD)',
                    'Purpose',
                    'Status (active/completed/pending)',
                ];
                $sampleData = [
                    [
                        'YLDA/26/0001',
                        '100000.00',
                        '5.00',
                        '12',
                        '2026-07-01',
                        'Equipment Purchase',
                        'active',
                    ],
                ];
                break;

            case 'expenses':
                $headers = [
                    'Category',
                    'Description',
                    'Amount (NGN)',
                    'Expense Date (YYYY-MM-DD)',
                    'Status (approved/pending/rejected)',
                ];
                $sampleData = [
                    [
                        'Office Supplies',
                        'Stationery and printer ink',
                        '15000.00',
                        '2026-07-10',
                        'approved',
                    ],
                ];
                break;

            case 'investments':
                $headers = [
                    'Investment Name',
                    'Type Slug',
                    'Description',
                    'Capital Amount (NGN)',
                    'Start Date (YYYY-MM-DD)',
                    'Status (active/completed/pending)',
                ];
                $sampleData = [
                    [
                        'Real Estate Project A',
                        'real-estate',
                        'Acquisition of land for development',
                        '5000000.00',
                        '2026-07-01',
                        'active',
                    ],
                ];
                break;

            case 'registration_fees':
                $headers = [
                    'Member Code',
                    'Fee Amount (NGN)',
                    'Payment Date (YYYY-MM-DD)',
                    'Payment Method',
                    'Reference Number',
                ];
                $sampleData = [
                    [
                        'YLDA/26/0001',
                        '1000.00',
                        '2026-07-01',
                        'Cash',
                        'REF-REG-001',
                    ],
                ];
                break;

            default:
                throw new \InvalidArgumentException("Invalid import type: {$type}");
        }

        $output = fopen('php://temp', 'r+');
        // Write UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $headers);
        foreach ($sampleData as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

    /**
     * Parse and validate uploaded CSV file.
     */
    public function parseAndValidate(UploadedFile $file, string $type, string $duplicateMode): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            throw new \RuntimeException("Could not open uploaded file.");
        }

        // Remove UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle);
        if (!$headers || count($headers) === 0) {
            fclose($handle);
            throw new \RuntimeException("Uploaded file is empty or invalid.");
        }

        // Clean headers
        $headers = array_map(function ($h) {
            return trim(preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $h));
        }, $headers);

        $this->assertHeadersMatchImportType($headers, $type);

        $parsedRows = [];
        $rowNumber = 1; // Header is row 1
        $totalRows = 0;
        $validCount = 0;
        $duplicateCount = 0;
        $invalidCount = 0;

        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;
            // Skip completely empty rows
            if (empty(array_filter($data))) {
                continue;
            }

            $totalRows++;
            $rowMap = [];
            foreach ($headers as $index => $header) {
                $rowMap[$header] = isset($data[$index]) ? trim($data[$index]) : '';
            }

            $validation = $this->validateRow($rowMap, $type, $rowNumber);
            $status = $validation['status'];
            $errors = $validation['errors'];

            if ($status === 'valid') {
                $validCount++;
            } elseif ($status === 'duplicate') {
                $duplicateCount++;
            } else {
                $invalidCount++;
            }

            $parsedRows[] = [
                'row_number' => $rowNumber,
                'data' => $rowMap,
                'status' => $status,
                'errors' => $errors,
                'meta' => $validation['meta'] ?? [],
            ];
        }

        fclose($handle);

        return [
            'total_rows' => $totalRows,
            'valid_count' => $validCount,
            'duplicate_count' => $duplicateCount,
            'invalid_count' => $invalidCount,
            'rows' => $parsedRows,
        ];
    }

    protected function assertHeadersMatchImportType(array $headers, string $type): void
    {
        $headerSet = array_fill_keys($headers, true);

        if ($type === 'members' && isset($headerSet['Member Code']) && isset($headerSet['Month (YYYY-MM)'])) {
            throw new \InvalidArgumentException(
                'This is a savings or running charges file. Select Monthly Savings or Running Charges as the import type.'
            );
        }

        $requiredHeaders = match ($type) {
            'savings' => ['Member Code', 'Month (YYYY-MM)', 'Amount (NGN)'],
            'running_charges' => ['Member Code', 'Month (YYYY-MM)', 'Amount (NGN)'],
            'loans' => ['Member Code', 'Principal Amount (NGN)', 'Duration (Months)'],
            'registration_fees' => ['Member Code', 'Fee Amount (NGN)'],
            default => [],
        };

        $missingHeaders = array_values(array_filter(
            $requiredHeaders,
            static fn (string $header): bool => !isset($headerSet[$header])
        ));

        if ($missingHeaders !== []) {
            throw new \InvalidArgumentException(
                "The selected import type '{$type}' requires these columns: " . implode(', ', $missingHeaders) . '.'
            );
        }
    }

    /**
     * Validate an individual row based on type.
     */
    protected function validateRow(array $row, string $type, int $rowNumber): array
    {
        $errors = [];
        $status = 'valid';
        $meta = [];

        switch ($type) {
            case 'members':
                $memberCode = trim((string) ($row['Member Code'] ?? ''));
                $email = strtolower($row['Email'] ?? '');
                $name = $row['Full Name'] ?? '';
                $slots = $row['Savings Slots (1-10)'] ?? '1';
                $role = strtolower($row['Role (member/chairman/secretary/treasurer/admin)'] ?? 'member');

                if (empty($name)) {
                    $errors[] = "Full Name is required.";
                }
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Valid Email address is required.";
                }
                if (!in_array($role, ['member', 'chairman', 'secretary', 'treasurer', 'admin'])) {
                    $errors[] = "Role must be member, chairman, secretary, treasurer, or admin.";
                }
                if (!is_numeric($slots) || (int)$slots < 1 || (int)$slots > 10) {
                    $errors[] = "Savings Slots must be an integer between 1 and 10.";
                }

                if (count($errors) === 0) {
                    $existingUser = !empty($memberCode)
                        ? User::where('member_code', $memberCode)->first()
                        : null;

                    if ($existingUser) {
                        $status = 'duplicate';
                        $errors[] = "Member record already exists with Member Code ({$existingUser->member_code}).";
                        $meta['existing_user_id'] = $existingUser->id;
                    }
                } else {
                    $status = 'invalid';
                }
                break;

            case 'savings':
                $memberCode = trim((string)($row['Member Code'] ?? ''));
                $slotNo = trim((string)($row['Current Slot No'] ?? $row['Slot Number'] ?? ''));
                $month = $row['Month (YYYY-MM)'] ?? '';
                $amount = $row['Amount (NGN)'] ?? '';

                if (empty($memberCode)) {
                    $errors[] = 'Member Code is required.';
                }
                if (!empty($slotNo) && (!is_numeric($slotNo) || (int)$slotNo < 1 || (int)$slotNo > 10)) {
                    $errors[] = 'Current Slot No must be a number between 1 and 10.';
                }
                if (empty($month) || false === strtotime($month . '-01')) {
                    $errors[] = 'Valid Month (YYYY-MM) is required.';
                }
                if (!is_numeric($amount) || (float)$amount < 0) {
                    $errors[] = 'Amount must be zero or a positive number.';
                }

                if (count($errors) === 0) {
                    $user = User::where('role', 'member')
                        ->where('member_code', $memberCode)
                        ->first();

                    if (!$user) {
                        $status = 'invalid';
                        $errors[] = "No member found matching Member Code: '{$memberCode}'.";
                    } else {
                        $meta['user_id'] = $user->id;
                        $formattedMonth = Carbon::parse($month . '-01')->format('Y-m-01');

                        $existingSaving = MonthlySaving::where('user_id', $user->id)
                            ->where('month', $formattedMonth)
                            ->first();

                        if ($existingSaving) {
                            $status = 'duplicate';
                            $errors[] = "Monthly Savings for '{$user->name}' for {$month} has already been recorded.";
                        }
                    }
                } else {
                    $status = 'invalid';
                }
                break;

            case 'running_charges':
                $memberCode = trim((string)($row['Member Code'] ?? ''));
                $month = $row['Month (YYYY-MM)'] ?? '';
                $amount = $row['Amount (NGN)'] ?? '';

                if (empty($memberCode)) {
                    $errors[] = 'Member Code is required.';
                }
                if (empty($month) || false === strtotime($month . '-01')) {
                    $errors[] = "Valid Month (YYYY-MM) is required.";
                }
                if (!is_numeric($amount) || (float)$amount <= 0) {
                    $errors[] = "Amount must be a positive number.";
                }

                if (count($errors) === 0) {
                    $user = User::where('role', 'member')
                        ->where('member_code', $memberCode)
                        ->first();

                    if (!$user) {
                        $status = 'invalid';
                        $errors[] = "No member found matching Member Code: '{$memberCode}'.";
                    } else {
                        $meta['user_id'] = $user->id;
                        $formattedMonth = Carbon::parse($month . '-01')->format('Y-m-01');

                        $existingCharge = RunningCharge::where('user_id', $user->id)
                            ->where('month', $formattedMonth)
                            ->first();

                        if ($existingCharge) {
                            $status = 'duplicate';
                            $errors[] = "Running charge for '{$user->name}' for {$month} is already recorded.";
                        }
                    }
                } else {
                    $status = 'invalid';
                }
                break;

            case 'loans':
                $memberCode = trim((string) ($row['Member Code'] ?? ''));
                $principal = $row['Principal Amount (NGN)'] ?? '';
                $profitRate = $row['Profit Rate (%)'] ?? '0';
                $duration = $row['Duration (Months)'] ?? '';
                $dateGranted = $row['Date Granted (YYYY-MM-DD)'] ?? '';

                if (empty($memberCode)) {
                    $errors[] = 'Member Code is required.';
                }
                if (!is_numeric($principal) || (float)$principal <= 0) {
                    $errors[] = "Principal Amount must be a positive number.";
                }
                if (!is_numeric($profitRate) || (float)$profitRate < 0) {
                    $errors[] = "Profit Rate (%) must be 0 or greater.";
                }
                if (!is_numeric($duration) || (int)$duration < 1) {
                    $errors[] = "Duration (Months) must be at least 1 month.";
                }
                if (!empty($dateGranted) && false === strtotime($dateGranted)) {
                    $errors[] = "Invalid Date Granted format.";
                }

                if (count($errors) === 0) {
                    $user = User::where('role', 'member')
                        ->where('member_code', $memberCode)
                        ->first();

                    if (!$user) {
                        $status = 'invalid';
                        $errors[] = "No member found matching Member Code: '{$memberCode}'.";
                    } else {
                        $meta['user_id'] = $user->id;
                    }
                } else {
                    $status = 'invalid';
                }
                break;

            case 'expenses':
                $category = $row['Category'] ?? '';
                $description = $row['Description'] ?? '';
                $amount = $row['Amount (NGN)'] ?? '';
                $expenseDate = $row['Expense Date (YYYY-MM-DD)'] ?? '';

                if (empty($category)) {
                    $errors[] = "Category is required.";
                }
                if (empty($description)) {
                    $errors[] = "Description is required.";
                }
                if (!is_numeric($amount) || (float)$amount <= 0) {
                    $errors[] = "Amount must be a positive number.";
                }
                if (!empty($expenseDate) && false === strtotime($expenseDate)) {
                    $errors[] = "Invalid Expense Date format (must be YYYY-MM-DD).";
                }

                if (count($errors) > 0) {
                    $status = 'invalid';
                }
                break;

            case 'investments':
                $name = $row['Investment Name'] ?? '';
                $amount = $row['Capital Amount (NGN)'] ?? '';
                $startDate = $row['Start Date (YYYY-MM-DD)'] ?? '';

                if (empty($name)) {
                    $errors[] = "Investment Name is required.";
                }
                if (!is_numeric($amount) || (float)$amount <= 0) {
                    $errors[] = "Capital Amount must be a positive number.";
                }
                if (!empty($startDate) && false === strtotime($startDate)) {
                    $errors[] = "Invalid Start Date format (must be YYYY-MM-DD).";
                }

                if (count($errors) === 0) {
                    $existing = \App\Models\Investment::where('name', $name)->first();
                    if ($existing) {
                        $status = 'duplicate';
                        $errors[] = "Investment with name '{$name}' already exists.";
                    }
                } else {
                    $status = 'invalid';
                }
                break;

            case 'registration_fees':
                $memberCode = trim((string) ($row['Member Code'] ?? ''));
                $amount = $row['Fee Amount (NGN)'] ?? '';
                $paymentDate = $row['Payment Date (YYYY-MM-DD)'] ?? '';

                if (empty($memberCode)) {
                    $errors[] = 'Member Code is required.';
                }
                if (!is_numeric($amount) || (float)$amount <= 0) {
                    $errors[] = "Fee Amount must be a positive number.";
                }
                if (!empty($paymentDate) && false === strtotime($paymentDate)) {
                    $errors[] = "Invalid Payment Date format.";
                }

                if (count($errors) === 0) {
                    $user = User::where('role', 'member')
                        ->where('member_code', $memberCode)
                        ->first();

                    if (!$user) {
                        $status = 'invalid';
                        $errors[] = "No member found matching Member Code: '{$memberCode}'.";
                    } else {
                        $meta['user_id'] = $user->id;
                    }
                } else {
                    $status = 'invalid';
                }
                break;
        }

        return [
            'status' => $status,
            'errors' => $errors,
            'meta' => $meta,
        ];
    }

    /**
     * Execute the bulk import transaction.
     */
    public function executeImport(array $previewRows, string $type, string $duplicateMode, int $uploaderId, string $filename): BatchImport
    {
        $successful = 0;
        $duplicatesSkipped = 0;
        $invalidSkipped = 0;
        $errorLogs = [];

        DB::transaction(function () use (
            $previewRows, $type, $duplicateMode, $uploaderId,
            &$successful, &$duplicatesSkipped, &$invalidSkipped, &$errorLogs
        ) {
            $regService = app(\App\Services\RegistrationFeeService::class);

            foreach ($previewRows as $row) {
                $status = $row['status'];
                $data = $row['data'];
                $errors = $row['errors'];
                $rowNum = $row['row_number'];
                $meta = $row['meta'] ?? [];

                if ($status === 'invalid') {
                    $invalidSkipped++;
                    $errorLogs[] = [
                        'row_number' => $rowNum,
                        'identifier' => $data['Member Code'] ?? $data['Email'] ?? 'Row ' . $rowNum,
                        'issue' => 'Validation Error',
                        'reason' => implode('; ', $errors),
                    ];
                    continue;
                }

                if ($status === 'duplicate') {
                    if ($duplicateMode === 'skip') {
                        $duplicatesSkipped++;
                        $errorLogs[] = [
                            'row_number' => $rowNum,
                            'identifier' => $data['Member Code'] ?? $data['Email'] ?? 'Row ' . $rowNum,
                            'issue' => 'Duplicate Skipped',
                            'reason' => implode('; ', $errors),
                        ];
                        continue;
                    }
                }

                // Process Import Row
                try {
                    switch ($type) {
                        case 'members':
                            $email = strtolower($data['Email']);
                            $name = $data['Full Name'];
                            $phone = $data['Phone Number'] ?? null;
                            $address = $data['Contact Address'] ?? null;
                            $regYear = !empty($data['Registration Year']) ? (int)$data['Registration Year'] : (int)date('Y');
                            $regMonth = (int) date('n');
                            if (!empty($data['Registration Month'])) {
                                if (is_numeric($data['Registration Month']) && (int)$data['Registration Month'] >= 1 && (int)$data['Registration Month'] <= 12) {
                                    $regMonth = (int) $data['Registration Month'];
                                } else {
                                    $parsedMonth = date('n', strtotime($data['Registration Month'] . ' 1'));
                                    if ($parsedMonth >= 1 && $parsedMonth <= 12) {
                                        $regMonth = (int) $parsedMonth;
                                    }
                                }
                            }
                            $role = !empty($data['Role (member/treasurer/admin)']) ? strtolower($data['Role (member/treasurer/admin)']) : 'member';
                            $slots = !empty($data['Savings Slots (1-10)']) ? (int)$data['Savings Slots (1-10)'] : 1;

                            if ($status === 'duplicate' && $duplicateMode === 'update' && isset($meta['existing_user_id'])) {
                                $user = User::findOrFail($meta['existing_user_id']);
                                $user->update([
                                    'name' => $name,
                                    'phone' => $phone ?: $user->phone,
                                    'address' => $address ?: $user->address,
                                ]);
                            } else {
                                $user = User::create([
                                    'name' => $name,
                                    'email' => $email,
                                    'password' => Hash::make('password'),
                                    'phone' => $phone,
                                    'address' => $address,
                                    'member_code' => $role === 'member' ? User::generateMemberCode($regYear) : null,
                                    'registration_year' => $role === 'member' ? $regYear : null,
                                    'registration_month' => $role === 'member' ? $regMonth : null,
                                    'role' => $role,
                                    'is_active' => true,
                                ]);

                                if ($role === 'member') {
                                    // Create slots
                                    for ($i = 1; $i <= $slots; $i++) {
                                        $user->savingsSlots()->create([
                                            'slot_number' => $i,
                                            'is_active' => true,
                                        ]);
                                    }

                                    // Create Next of Kin if provided
                                    if (!empty($data['Next of Kin Name'])) {
                                        $user->nextOfKin()->create([
                                            'name' => $data['Next of Kin Name'],
                                            'phone' => $data['Next of Kin Phone'] ?? 'N/A',
                                            'relationship' => $data['Next of Kin Relationship'] ?? 'Relative',
                                            'address' => $data['Next of Kin Address'] ?? null,
                                        ]);
                                    }

                                    // Create registration fee obligation and full payment
                                    $feeAmount = $regService->getCurrentFeeAmount();
                                    $fee = $regService->createObligationForMember($user, $feeAmount);
                                    $regService->recordPayment($fee, $feeAmount, date('Y-m-d'), 'Cash', 'BULK-IMPORT', $uploaderId);
                                }
                            }
                            $successful++;
                            break;

                        case 'savings':
                            $userId = $meta['user_id'];
                            $monthInput = $data['Month (YYYY-MM)'];
                            $formattedMonth = Carbon::parse($monthInput . '-01')->format('Y-m-01');
                            $amount = (float)$data['Amount (NGN)'];
                            $paymentDate = !empty($data['Payment Date (YYYY-MM-DD)']) ? Carbon::parse($data['Payment Date (YYYY-MM-DD)'])->format('Y-m-d') : date('Y-m-d');
                            $slotNum = !empty($data['Current Slot No']) ? (int)$data['Current Slot No'] : (!empty($data['Slot Number']) ? (int)$data['Slot Number'] : 1);

                            $user = User::findOrFail($userId);
                            $slot = $user->savingsSlots()->where('slot_number', $slotNum)->first();
                            if (!$slot) {
                                $slot = $user->savingsSlots()->first() ?? $user->savingsSlots()->create(['slot_number' => 1, 'is_active' => true]);
                            }

                            MonthlySaving::create([
                                'user_id' => $user->id,
                                'savings_slot_id' => $slot->id,
                                'amount' => $amount,
                                'month' => $formattedMonth,
                                'payment_date' => $paymentDate,
                                'status' => 'paid',
                                'recorded_by' => $uploaderId,
                            ]);
                            $successful++;
                            break;

                        case 'running_charges':
                            $userId = $meta['user_id'];
                            $monthInput = $data['Month (YYYY-MM)'];
                            $formattedMonth = Carbon::parse($monthInput . '-01')->format('Y-m-01');
                            $amount = (float)$data['Amount (NGN)'];
                            $paymentDate = !empty($data['Payment Date (YYYY-MM-DD)']) ? Carbon::parse($data['Payment Date (YYYY-MM-DD)'])->format('Y-m-d') : date('Y-m-d');

                            RunningCharge::create([
                                'user_id' => $userId,
                                'amount' => $amount,
                                'month' => $formattedMonth,
                                'payment_date' => $paymentDate,
                                'status' => 'paid',
                                'recorded_by' => $uploaderId,
                            ]);
                            $successful++;
                            break;

                        case 'loans':
                            $userId = $meta['user_id'];
                            $principal = (float)$data['Principal Amount (NGN)'];
                            $profitRate = (float)($data['Profit Rate (%)'] ?? 0);
                            $duration = (int)$data['Duration (Months)'];
                            $dateGranted = !empty($data['Date Granted (YYYY-MM-DD)']) ? Carbon::parse($data['Date Granted (YYYY-MM-DD)'])->format('Y-m-d') : date('Y-m-d');
                            $purpose = $data['Purpose'] ?? 'General Financing';
                            $statusInput = !empty($data['Status (active/completed/pending)']) ? strtolower($data['Status (active/completed/pending)']) : 'active';

                            $totalAmount = $principal + ($principal * ($profitRate / 100));
                            $monthlyPayment = $duration > 0 ? ($totalAmount / $duration) : $totalAmount;

                            Loan::create([
                                'user_id' => $userId,
                                'principal_amount' => $principal,
                                'profit_rate' => $profitRate,
                                'total_amount' => $totalAmount,
                                'monthly_payment' => $monthlyPayment,
                                'duration_months' => $duration,
                                'remaining_months' => $statusInput === 'completed' ? 0 : $duration,
                                'date_granted' => $dateGranted,
                                'status' => $statusInput,
                                'approved_by' => $uploaderId,
                            ]);
                            $successful++;
                            break;

                        case 'expenses':
                            $category = $data['Category'];
                            $description = $data['Description'];
                            $amount = (float)$data['Amount (NGN)'];
                            $expenseDate = !empty($data['Expense Date (YYYY-MM-DD)']) ? Carbon::parse($data['Expense Date (YYYY-MM-DD)'])->format('Y-m-d') : date('Y-m-d');
                            $statusInput = !empty($data['Status (approved/pending/rejected)']) ? strtolower($data['Status (approved/pending/rejected)']) : 'approved';

                            \App\Models\Expense::create([
                                'category' => $category,
                                'description' => $description,
                                'amount' => $amount,
                                'expense_date' => $expenseDate,
                                'status' => $statusInput,
                                'requested_by' => $uploaderId,
                                'approved_by' => $statusInput === 'approved' ? $uploaderId : null,
                                'approved_at' => $statusInput === 'approved' ? now() : null,
                            ]);
                            $successful++;
                            break;

                        case 'investments':
                            $name = $data['Investment Name'];
                            $typeSlug = !empty($data['Type Slug']) ? strtolower($data['Type Slug']) : 'real-estate';
                            $desc = $data['Description'] ?? null;
                            $amount = (float)$data['Capital Amount (NGN)'];
                            $startDate = !empty($data['Start Date (YYYY-MM-DD)']) ? Carbon::parse($data['Start Date (YYYY-MM-DD)'])->format('Y-m-d') : date('Y-m-d');
                            $statusInput = !empty($data['Status (active/completed/pending)']) ? strtolower($data['Status (active/completed/pending)']) : 'active';

                            if ($status === 'duplicate' && $duplicateMode === 'update') {
                                $inv = \App\Models\Investment::where('name', $name)->first();
                                if ($inv) {
                                    $inv->update([
                                        'capital_amount' => $amount,
                                        'description' => $desc ?: $inv->description,
                                        'status' => $statusInput,
                                    ]);
                                }
                            } else {
                                \App\Models\Investment::create([
                                    'name' => $name,
                                    'type' => $typeSlug,
                                    'description' => $desc,
                                    'capital_amount' => $amount,
                                    'total_returns' => 0.00,
                                    'start_date' => $startDate,
                                    'status' => $statusInput,
                                    'created_by' => $uploaderId,
                                ]);
                            }
                            $successful++;
                            break;

                        case 'registration_fees':
                            $userId = $meta['user_id'];
                            $amount = (float)$data['Fee Amount (NGN)'];
                            $paymentDate = !empty($data['Payment Date (YYYY-MM-DD)']) ? Carbon::parse($data['Payment Date (YYYY-MM-DD)'])->format('Y-m-d') : date('Y-m-d');
                            $method = $data['Payment Method'] ?? 'Cash';
                            $ref = $data['Reference Number'] ?? 'BULK-REG-IMPORT';

                            $user = User::findOrFail($userId);
                            $fee = $regService->createObligationForMember($user, $amount);
                            $regService->recordPayment($fee, $amount, $paymentDate, $method, $ref, $uploaderId);
                            $successful++;
                            break;
                    }
                } catch (\Exception $e) {
                    $invalidSkipped++;
                    $errorLogs[] = [
                        'row_number' => $rowNum,
                        'identifier' => $data['Member Code'] ?? $data['Email'] ?? 'Row ' . $rowNum,
                        'issue' => 'Database Execution Failure',
                        'reason' => $e->getMessage(),
                    ];
                }
            }
        });

        // Record Audit Trail
        $total = count($previewRows);
        return BatchImport::create([
            'user_id' => $uploaderId,
            'import_type' => $type,
            'original_filename' => $filename,
            'duplicate_mode' => $duplicateMode,
            'total_rows' => $total,
            'successful_rows' => $successful,
            'duplicate_rows' => $duplicatesSkipped,
            'invalid_rows' => $invalidSkipped,
            'status' => 'completed',
            'error_details' => $errorLogs,
        ]);
    }

    /**
     * Export error report CSV for a batch import.
     */
    public function generateErrorReportCsv(BatchImport $import): string
    {
        $headers = ['Row Number', 'Identifier / Record', 'Data Issue', 'Reason for Rejection'];
        $errorLogs = $import->error_details ?? [];

        $output = fopen('php://temp', 'r+');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $headers);

        foreach ($errorLogs as $err) {
            fputcsv($output, [
                $err['row_number'] ?? 'N/A',
                $err['identifier'] ?? 'N/A',
                $err['issue'] ?? 'Rejection',
                $err['reason'] ?? 'Invalid Data',
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }
}
