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
    public function generateTemplate(string $type): string
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
                    'Date of Birth (YYYY-MM-DD)',
                    'Registration Year',
                    'Role (member/treasurer/admin)',
                    'Savings Slots (1-10)',
                    'Next of Kin Name',
                    'Next of Kin Phone',
                    'Next of Kin Relationship',
                    'Next of Kin Email',
                    'Next of Kin Address',
                ];
                $sampleData = [
                    [
                        'John Doe',
                        'john.doe@example.com',
                        '08012345678',
                        '123 Cooperative Street, Lagos',
                        '1990-05-15',
                        '2026',
                        'member',
                        '2',
                        'Jane Doe',
                        '08087654321',
                        'Spouse',
                        'jane.doe@example.com',
                        '123 Cooperative Street, Lagos',
                    ],
                ];
                break;

            case 'savings':
                $headers = [
                    'Member Identifier (Email or Member Code)',
                    'Month (YYYY-MM)',
                    'Amount (NGN)',
                    'Payment Date (YYYY-MM-DD)',
                    'Slot Number',
                ];
                $sampleData = [
                    [
                        'john.doe@example.com',
                        '2026-07',
                        '4000.00',
                        '2026-07-05',
                        '1',
                    ],
                ];
                break;

            case 'running_charges':
                $headers = [
                    'Member Identifier (Email or Member Code)',
                    'Month (YYYY-MM)',
                    'Amount (NGN)',
                    'Payment Date (YYYY-MM-DD)',
                ];
                $sampleData = [
                    [
                        'john.doe@example.com',
                        '2026-07',
                        '500.00',
                        '2026-07-05',
                    ],
                ];
                break;

            case 'loans':
                $headers = [
                    'Member Identifier (Email or Member Code)',
                    'Principal Amount (NGN)',
                    'Profit Rate (%)',
                    'Duration (Months)',
                    'Date Granted (YYYY-MM-DD)',
                    'Purpose',
                    'Status (active/completed/pending)',
                ];
                $sampleData = [
                    [
                        'john.doe@example.com',
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
                    'Member Identifier (Email or Member Code)',
                    'Fee Amount (NGN)',
                    'Payment Date (YYYY-MM-DD)',
                    'Payment Method',
                    'Reference Number',
                ];
                $sampleData = [
                    [
                        'john.doe@example.com',
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
                $email = strtolower($row['Email'] ?? '');
                $name = $row['Full Name'] ?? '';
                $dob = $row['Date of Birth (YYYY-MM-DD)'] ?? '';
                $slots = $row['Savings Slots (1-10)'] ?? '1';
                $role = strtolower($row['Role (member/treasurer/admin)'] ?? 'member');

                if (empty($name)) {
                    $errors[] = "Full Name is required.";
                }
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Valid Email address is required.";
                }
                if (!empty($dob) && false === strtotime($dob)) {
                    $errors[] = "Invalid Date of Birth format (must be YYYY-MM-DD).";
                }
                if (!in_array($role, ['member', 'treasurer', 'admin'])) {
                    $errors[] = "Role must be member, treasurer, or admin.";
                }
                if (!is_numeric($slots) || (int)$slots < 1 || (int)$slots > 10) {
                    $errors[] = "Savings Slots must be an integer between 1 and 10.";
                }

                if (count($errors) === 0) {
                    // Check duplicate in DB by email
                    $existingUser = User::where('email', $email)->first();

                    if ($existingUser) {
                        $status = 'duplicate';
                        $errors[] = "Member record already exists with Email ({$email})" . (!empty($existingUser->member_code) ? " or Member Code ({$existingUser->member_code})" : "") . ".";
                        $meta['existing_user_id'] = $existingUser->id;
                    }
                } else {
                    $status = 'invalid';
                }
                break;

            case 'savings':
                $identifier = $row['Member Identifier (Email or Member Code)'] ?? '';
                $month = $row['Month (YYYY-MM)'] ?? '';
                $amount = $row['Amount (NGN)'] ?? '';

                if (empty($identifier)) {
                    $errors[] = "Member Identifier (Email or Member Code) is required.";
                }
                if (empty($month) || false === strtotime($month . '-01')) {
                    $errors[] = "Valid Month (YYYY-MM) is required.";
                }
                if (!is_numeric($amount) || (float)$amount <= 0) {
                    $errors[] = "Amount must be a positive number.";
                }

                if (count($errors) === 0) {
                    $user = User::where('email', strtolower($identifier))
                        ->orWhere('member_code', $identifier)
                        ->first();

                    if (!$user) {
                        $status = 'invalid';
                        $errors[] = "No member found matching identifier: '{$identifier}'.";
                    } else {
                        $meta['user_id'] = $user->id;
                        $formattedMonth = Carbon::parse($month . '-01')->format('Y-m-01');

                        // Check duplicate monthly saving
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
                $identifier = $row['Member Identifier (Email or Member Code)'] ?? '';
                $month = $row['Month (YYYY-MM)'] ?? '';
                $amount = $row['Amount (NGN)'] ?? '';

                if (empty($identifier)) {
                    $errors[] = "Member Identifier is required.";
                }
                if (empty($month) || false === strtotime($month . '-01')) {
                    $errors[] = "Valid Month (YYYY-MM) is required.";
                }
                if (!is_numeric($amount) || (float)$amount <= 0) {
                    $errors[] = "Amount must be a positive number.";
                }

                if (count($errors) === 0) {
                    $user = User::where('email', strtolower($identifier))
                        ->orWhere('member_code', $identifier)
                        ->first();

                    if (!$user) {
                        $status = 'invalid';
                        $errors[] = "No member found matching identifier: '{$identifier}'.";
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
                $identifier = $row['Member Identifier (Email or Member Code)'] ?? '';
                $principal = $row['Principal Amount (NGN)'] ?? '';
                $profitRate = $row['Profit Rate (%)'] ?? '0';
                $duration = $row['Duration (Months)'] ?? '';
                $dateGranted = $row['Date Granted (YYYY-MM-DD)'] ?? '';

                if (empty($identifier)) {
                    $errors[] = "Member Identifier is required.";
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
                    $user = User::where('email', strtolower($identifier))
                        ->orWhere('member_code', $identifier)
                        ->first();

                    if (!$user) {
                        $status = 'invalid';
                        $errors[] = "No member found matching identifier: '{$identifier}'.";
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
                $identifier = $row['Member Identifier (Email or Member Code)'] ?? '';
                $amount = $row['Fee Amount (NGN)'] ?? '';
                $paymentDate = $row['Payment Date (YYYY-MM-DD)'] ?? '';

                if (empty($identifier)) {
                    $errors[] = "Member Identifier is required.";
                }
                if (!is_numeric($amount) || (float)$amount <= 0) {
                    $errors[] = "Fee Amount must be a positive number.";
                }
                if (!empty($paymentDate) && false === strtotime($paymentDate)) {
                    $errors[] = "Invalid Payment Date format.";
                }

                if (count($errors) === 0) {
                    $user = User::where('email', strtolower($identifier))
                        ->orWhere('member_code', $identifier)
                        ->first();

                    if (!$user) {
                        $status = 'invalid';
                        $errors[] = "No member found matching identifier: '{$identifier}'.";
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
                        'identifier' => $data['Email'] ?? $data['Member Identifier (Email or Member Code)'] ?? 'Row ' . $rowNum,
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
                            'identifier' => $data['Email'] ?? $data['Member Identifier (Email or Member Code)'] ?? 'Row ' . $rowNum,
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
                            $dob = !empty($data['Date of Birth (YYYY-MM-DD)']) ? Carbon::parse($data['Date of Birth (YYYY-MM-DD)'])->format('Y-m-d') : null;
                            $regYear = !empty($data['Registration Year']) ? (int)$data['Registration Year'] : (int)date('Y');
                            $role = !empty($data['Role (member/treasurer/admin)']) ? strtolower($data['Role (member/treasurer/admin)']) : 'member';
                            $slots = !empty($data['Savings Slots (1-10)']) ? (int)$data['Savings Slots (1-10)'] : 1;

                            if ($status === 'duplicate' && $duplicateMode === 'update' && isset($meta['existing_user_id'])) {
                                $user = User::findOrFail($meta['existing_user_id']);
                                $user->update([
                                    'name' => $name,
                                    'phone' => $phone ?: $user->phone,
                                    'address' => $address ?: $user->address,
                                    'date_of_birth' => $dob ?: $user->date_of_birth,
                                ]);
                            } else {
                                $user = User::create([
                                    'name' => $name,
                                    'email' => $email,
                                    'password' => Hash::make('password'),
                                    'phone' => $phone,
                                    'address' => $address,
                                    'date_of_birth' => $dob,
                                    'member_code' => $role === 'member' ? User::generateMemberCode($regYear) : null,
                                    'registration_year' => $role === 'member' ? $regYear : null,
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
                                            'email' => $data['Next of Kin Email'] ?? null,
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
                            $slotNum = !empty($data['Slot Number']) ? (int)$data['Slot Number'] : 1;

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
                        'identifier' => $data['Email'] ?? $data['Member Identifier (Email or Member Code)'] ?? 'Row ' . $rowNum,
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
