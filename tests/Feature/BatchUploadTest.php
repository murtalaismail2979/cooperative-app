<?php

namespace Tests\Feature;

use App\Models\BatchImport;
use App\Models\Loan;
use App\Models\MonthlySaving;
use App\Models\RunningCharge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BatchUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $treasurer;
    protected User $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->treasurer = User::factory()->create(['role' => 'treasurer']);
        $this->member = User::factory()->create(['role' => 'member']);
    }

    public function test_admin_and_treasurer_can_access_batch_upload_page(): void
    {
        $responseAdmin = $this->actingAs($this->admin)->get('/admin/batch-upload');
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('Batch Upload / Bulk Import');

        $responseTreasurer = $this->actingAs($this->treasurer)->get('/admin/batch-upload');
        $responseTreasurer->assertStatus(200);
    }

    public function test_member_cannot_access_batch_upload_page(): void
    {
        $response = $this->actingAs($this->member)->get('/admin/batch-upload');
        $response->assertStatus(403);
    }

    public function test_can_download_sample_csv_templates(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/batch-upload/template/members');
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertDontSee('Registration Number');
        $response->assertSee('Full Name');

        $memberUser = User::factory()->create([
            'role' => 'member',
            'name' => 'Savings Template User',
            'member_code' => 'MEM-2026-555',
        ]);
        $memberUser->savingsSlots()->create(['slot_number' => 2, 'is_active' => true]);

        $responseSavings = $this->actingAs($this->admin)->get('/admin/batch-upload/template/savings');
        $responseSavings->assertStatus(200);
        $responseSavings->assertSee('Member Code');
        $responseSavings->assertSee('Full Name');
        $responseSavings->assertSee('Current Slot No');
        $responseSavings->assertSee('MEM-2026-555');
        $responseSavings->assertSee('Savings Template User');
        $responseSavings->assertDontSee('Email');
    }

    public function test_running_charges_template_lists_members_by_code_with_full_names(): void
    {
        $firstMember = User::factory()->create([
            'role' => 'member',
            'name' => 'Zulu Member',
            'member_code' => 'YLDA/26/0002',
        ]);
        $secondMember = User::factory()->create([
            'role' => 'member',
            'name' => 'Alpha Member',
            'member_code' => 'YLDA/26/0001',
        ]);
        User::factory()->create([
            'role' => 'treasurer',
            'name' => 'Treasurer Account',
            'member_code' => 'MGMT-001',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/batch-upload/template/running_charges');

        $response->assertStatus(200);
        $response->assertSee('Member Code');
        $response->assertSee('Full Name');
        $response->assertSee('Month (YYYY-MM)');
        $response->assertSee('Payment Date (YYYY-MM-DD)');
        $response->assertSee('YLDA/26/0001');
        $response->assertSee('Alpha Member');
        $response->assertSee('YLDA/26/0002');
        $response->assertSee('Zulu Member');
        $csvRows = array_filter(
            preg_split('/\r\n|\r|\n/', trim($response->getContent())),
            static fn (string $row): bool => $row !== '' && !str_starts_with(ltrim($row, "\xEF\xBB\xBF"), '"Member Code"')
        );
        $this->assertCount(102, $csvRows);
        $this->assertSame(51, count(array_filter($csvRows, fn (string $row): bool => str_contains($row, $firstMember->member_code))));
        $this->assertSame(51, count(array_filter($csvRows, fn (string $row): bool => str_contains($row, $secondMember->member_code))));
        $response->assertDontSee('MGMT-001');
        $response->assertDontSee('Treasurer Account');
        $this->assertLessThan(
            strpos($response->getContent(), 'YLDA/26/0002'),
            strpos($response->getContent(), 'YLDA/26/0001')
        );
    }

    public function test_running_charges_template_can_be_limited_to_november_and_december_2021(): void
    {
        User::factory()->create([
            'role' => 'member',
            'name' => 'Late 2021 Member',
            'member_code' => 'YLDA/26/0100',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/batch-upload/template/running_charges?late_2021=1');

        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="coop_batch_template_running_charges_2021-11_to_2021-12.csv"');
        $response->assertDontSee('2021-10');
        $response->assertSee('2021-11');
        $response->assertSee('2021-12');
        $response->assertDontSee('2022-01');
        $response->assertDontSee('2026-01');
    }

    public function test_savings_batch_uses_member_code_only_for_identifier(): void
    {
        $memberUser = User::factory()->create([
            'email' => 'membercodeonly@example.com',
            'name' => 'Member Code User',
            'member_code' => 'YLDA/26/0007',
        ]);
        $memberUser->savingsSlots()->create(['slot_number' => 1, 'is_active' => true]);

        $csvContent = "\xEF\xBB\xBF" . "Member Code,Current Slot No,Month (YYYY-MM),Amount (NGN),Payment Date (YYYY-MM-DD)\n" .
            "YLDA/26/0007,1,2026-07,5000.00,2026-07-10\n";

        $file = UploadedFile::fake()->createWithContent('savings.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post('/admin/batch-upload/preview', [
            'import_type' => 'savings',
            'duplicate_mode' => 'skip',
            'import_file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertSee('YLDA/26/0007');
        $response->assertDontSee('No member found matching identifier');
    }

    public function test_running_charge_file_selected_as_members_gives_type_error_not_email_error(): void
    {
        $csvContent = "\xEF\xBB\xBF" . "Member Code,Full Name,Month (YYYY-MM),Amount (NGN),Payment Date (YYYY-MM-DD)\n" .
            "MEM-2026-999,Member Name,2026-07,500.00,2026-07-05\n";

        $file = UploadedFile::fake()->createWithContent('running-charges.csv', $csvContent);

        $response = $this->actingAs($this->admin)->from('/admin/batch-upload')->post('/admin/batch-upload/preview', [
            'import_type' => 'members',
            'duplicate_mode' => 'skip',
            'import_file' => $file,
        ]);

        $response->assertRedirect('/admin/batch-upload');
        $response->assertSessionHasErrors('import_file');
        $response->assertSessionDoesntHaveErrors('email');
        $this->assertStringContainsString('Select Monthly Savings or Running Charges', session('errors')->first('import_file'));
    }

    public function test_savings_batch_ignores_email_column(): void
    {
        $memberUser = User::factory()->create([
            'member_code' => 'MEM-EMAIL-IGNORED',
        ]);

        $csvContent = "\xEF\xBB\xBF" . "Member Code,Email,Current Slot No,Month (YYYY-MM),Amount (NGN),Payment Date (YYYY-MM-DD)\n" .
            "MEM-EMAIL-IGNORED,not-an-email,1,2026-08,5000.00,2026-08-10\n";

        $file = UploadedFile::fake()->createWithContent('savings-with-email-column.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post('/admin/batch-upload/preview', [
            'import_type' => 'savings',
            'duplicate_mode' => 'skip',
            'import_file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertSee('Valid');
        $response->assertDontSee('Valid Email address is required.');
    }

    public function test_savings_batch_allows_zero_amount(): void
    {
        $memberUser = User::factory()->create([
            'member_code' => 'MEM-ZERO-AMOUNT',
        ]);
        $memberUser->savingsSlots()->create(['slot_number' => 1, 'is_active' => true]);

        $csvContent = "\xEF\xBB\xBF" . "Member Code,Current Slot No,Month (YYYY-MM),Amount (NGN),Payment Date (YYYY-MM-DD)\n" .
            "MEM-ZERO-AMOUNT,1,2026-09,0.00,2026-09-10\n";

        $file = UploadedFile::fake()->createWithContent('zero-savings.csv', $csvContent);

        $this->actingAs($this->admin)->post('/admin/batch-upload/preview', [
            'import_type' => 'savings',
            'duplicate_mode' => 'skip',
            'import_file' => $file,
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/batch-upload/confirm');

        $response->assertRedirect('/admin/batch-upload');
        $this->assertDatabaseHas('monthly_savings', [
            'user_id' => $memberUser->id,
            'amount' => 0,
        ]);
    }

    public function test_savings_batch_rejects_management_role_member_code(): void
    {
        $manager = User::factory()->create([
            'role' => 'treasurer',
            'member_code' => 'MGMT-001',
        ]);

        $csvContent = "\xEF\xBB\xBF" . "Member Code,Current Slot No,Month (YYYY-MM),Amount (NGN),Payment Date (YYYY-MM-DD)\n" .
            "MGMT-001,1,2026-08,5000.00,2026-08-10\n";

        $file = UploadedFile::fake()->createWithContent('management-savings.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post('/admin/batch-upload/preview', [
            'import_type' => 'savings',
            'duplicate_mode' => 'skip',
            'import_file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertSee('Invalid');
        $response->assertSee('No member found matching Member Code');
        $this->assertDatabaseMissing('monthly_savings', ['user_id' => $manager->id]);
    }

    public function test_upload_valid_members_csv_file_generates_preview(): void
    {
        $csvContent = "\xEF\xBB\xBF" . "Full Name,Email,Phone Number,Contact Address,Registration Year,Role (member/treasurer/admin),Savings Slots (1-10),Next of Kin Name,Next of Kin Phone,Next of Kin Relationship,Next of Kin Address\n" .
            "Alice Smith,alice@example.com,08011112222,Lagos Nigeria,2026,member,2,Bob Smith,08033334444,Spouse,Lagos\n" .
            "Charlie Brown,charlie@example.com,08055556666,Abuja,2026,member,1,David Brown,08077778888,Brother,Abuja\n";

        $file = UploadedFile::fake()->createWithContent('members.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post('/admin/batch-upload/preview', [
            'import_type' => 'members',
            'duplicate_mode' => 'skip',
            'import_file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertSee('Alice Smith');
        $response->assertSee('Charlie Brown');
        $response->assertSee('Confirm and Execute Import');
    }

    public function test_upload_invalid_file_format_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)->from('/admin/batch-upload')->post('/admin/batch-upload/preview', [
            'import_type' => 'members',
            'duplicate_mode' => 'skip',
            'import_file' => $file,
        ]);

        $response->assertRedirect('/admin/batch-upload');
        $response->assertSessionHasErrors('import_file');
    }

    public function test_upload_oversized_file_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('large.csv', 12000, 'text/csv'); // >10MB

        $response = $this->actingAs($this->admin)->from('/admin/batch-upload')->post('/admin/batch-upload/preview', [
            'import_type' => 'members',
            'duplicate_mode' => 'skip',
            'import_file' => $file,
        ]);

        $response->assertRedirect('/admin/batch-upload');
        $response->assertSessionHasErrors('import_file');
    }

    public function test_confirm_import_creates_members_slots_next_of_kin_and_audit_trail(): void
    {
        $csvContent = "\xEF\xBB\xBF" . "Full Name,Email,Phone Number,Contact Address,Registration Year,Role (member/treasurer/admin),Savings Slots (1-10),Next of Kin Name,Next of Kin Phone,Next of Kin Relationship,Next of Kin Address\n" .
            "New User,newuser@example.com,08099990000,Enugu,2026,member,3,Kin User,08011110000,Parent,Enugu\n";

        $file = UploadedFile::fake()->createWithContent('members.csv', $csvContent);

        // Preview step
        $this->actingAs($this->admin)->post('/admin/batch-upload/preview', [
            'import_type' => 'members',
            'duplicate_mode' => 'skip',
            'import_file' => $file,
        ]);

        // Confirm step
        $response = $this->actingAs($this->admin)->post('/admin/batch-upload/confirm');
        $response->assertRedirect('/admin/batch-upload');
        $response->assertSessionHas('success');

        // Assert user created
        $createdUser = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($createdUser);
        $this->assertEquals('New User', $createdUser->name);
        $this->assertStringStartsWith('YLDA/26/', $createdUser->member_code);
        $this->assertEquals(3, $createdUser->savingsSlots()->count());
        $this->assertNotNull($createdUser->nextOfKin);
        $this->assertEquals('Kin User', $createdUser->nextOfKin->name);

        // Assert Audit Trail
        $audit = BatchImport::where('import_type', 'members')->first();
        $this->assertNotNull($audit);
        $this->assertEquals(1, $audit->successful_rows);
        $this->assertEquals(0, $audit->invalid_rows);
    }

    public function test_batch_upload_ignores_obsolete_registration_number_column_and_auto_generates_code(): void
    {
        $csvContent = "\xEF\xBB\xBF" . "Registration Number,Full Name,Email,Phone Number,Contact Address,Registration Year,Role (member/treasurer/admin),Savings Slots (1-10),Next of Kin Name,Next of Kin Phone,Next of Kin Relationship,Next of Kin Address\n" .
            "OBSOLETE-999,Legacy User,legacy@example.com,08099990001,Enugu,2026,member,1,Kin Legacy,08011110001,Parent,Enugu\n";

        $file = UploadedFile::fake()->createWithContent('old_members.csv', $csvContent);

        $this->actingAs($this->admin)->post('/admin/batch-upload/preview', [
            'import_type' => 'members',
            'duplicate_mode' => 'skip',
            'import_file' => $file,
        ]);

        $this->actingAs($this->admin)->post('/admin/batch-upload/confirm');

        $user = User::where('email', 'legacy@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotEquals('OBSOLETE-999', $user->member_code);
        $this->assertStringStartsWith('YLDA/26/', $user->member_code);
    }

    public function test_batch_upload_generates_unique_member_codes_for_large_batch(): void
    {
        $rows = [];
        for ($i = 1; $i <= 5; $i++) {
            $rows[] = "Member {$i},member{$i}@example.com,0800000000{$i},Lagos,2026,member,1,Kin {$i},0809999990{$i},Sibling,Lagos";
        }
        $csvContent = "\xEF\xBB\xBF" . "Full Name,Email,Phone Number,Contact Address,Registration Year,Role (member/treasurer/admin),Savings Slots (1-10),Next of Kin Name,Next of Kin Phone,Next of Kin Relationship,Next of Kin Address\n" . implode("\n", $rows) . "\n";

        $file = UploadedFile::fake()->createWithContent('batch_members.csv', $csvContent);

        $this->actingAs($this->admin)->post('/admin/batch-upload/preview', [
            'import_type' => 'members',
            'duplicate_mode' => 'skip',
            'import_file' => $file,
        ]);

        $this->actingAs($this->admin)->post('/admin/batch-upload/confirm');

        $importedMembers = User::whereIn('email', array_map(fn($i) => "member{$i}@example.com", range(1, 5)))->get();
        $this->assertCount(5, $importedMembers);

        $codes = $importedMembers->pluck('member_code')->toArray();
        $this->assertCount(5, array_unique($codes));
        foreach ($codes as $code) {
            $this->assertStringStartsWith('YLDA/26/', $code);
        }
    }

    public function test_bulk_import_monthly_savings(): void
    {
        $memberUser = User::factory()->create([
            'email' => 'savingsuser@example.com',
            'name' => 'Savings User',
            'member_code' => 'MEM-2026-555',
        ]);
        $memberUser->savingsSlots()->create(['slot_number' => 1, 'is_active' => true]);

        $csvContent = "\xEF\xBB\xBF" . "Member Code,Current Slot No,Month (YYYY-MM),Amount (NGN),Payment Date (YYYY-MM-DD)\n" .
            "MEM-2026-555,1,2026-07,5000.00,2026-07-10\n";

        $file = UploadedFile::fake()->createWithContent('savings.csv', $csvContent);

        $this->actingAs($this->admin)->post('/admin/batch-upload/preview', [
            'import_type' => 'savings',
            'duplicate_mode' => 'skip',
            'import_file' => $file,
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/batch-upload/confirm');
        $response->assertRedirect('/admin/batch-upload');

        $saving = MonthlySaving::where('user_id', $memberUser->id)->first();
        $this->assertNotNull($saving);
        $this->assertEquals(5000.00, $saving->amount);
    }

    public function test_bulk_import_running_charges(): void
    {
        $memberUser = User::factory()->create([
            'email' => 'chargeuser@example.com',
            'member_code' => 'MEM-2026-666',
        ]);

        $csvContent = "\xEF\xBB\xBF" . "Member Code,Full Name,Month (YYYY-MM),Amount (NGN),Payment Date (YYYY-MM-DD)\n" .
            "MEM-2026-666,Charge User,2026-07,500.00,2026-07-05\n";

        $file = UploadedFile::fake()->createWithContent('charges.csv', $csvContent);

        $this->actingAs($this->admin)->post('/admin/batch-upload/preview', [
            'import_type' => 'running_charges',
            'duplicate_mode' => 'skip',
            'import_file' => $file,
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/batch-upload/confirm');
        $response->assertRedirect('/admin/batch-upload');

        $charge = RunningCharge::where('user_id', $memberUser->id)->first();
        $this->assertNotNull($charge);
        $this->assertEquals(500.00, $charge->amount);
    }

    public function test_bulk_import_financing_loans(): void
    {
        $memberUser = User::factory()->create([
            'email' => 'loanuser@example.com',
            'member_code' => 'MEM-2026-777',
        ]);

        $csvContent = "\xEF\xBB\xBF" . "Member Code,Principal Amount (NGN),Profit Rate (%),Duration (Months),Date Granted (YYYY-MM-DD),Purpose,Status (active/completed/pending)\n" .
            "MEM-2026-777,200000.00,10.00,10,2026-07-01,Business Expansion,active\n";

        $file = UploadedFile::fake()->createWithContent('loans.csv', $csvContent);

        $this->actingAs($this->admin)->post('/admin/batch-upload/preview', [
            'import_type' => 'loans',
            'duplicate_mode' => 'skip',
            'import_file' => $file,
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/batch-upload/confirm');
        $response->assertRedirect('/admin/batch-upload');

        $loan = Loan::where('user_id', $memberUser->id)->first();
        $this->assertNotNull($loan);
        $this->assertEquals(200000.00, $loan->principal_amount);
        $this->assertEquals(220000.00, $loan->total_amount); // 200,000 + 10%
    }

    public function test_duplicate_detection_and_skip_policy(): void
    {
        User::factory()->create(['email' => 'existing@example.com', 'name' => 'Original Name']);

        $csvContent = "\xEF\xBB\xBF" . "Registration Number,Full Name,Email,Phone Number,Contact Address,Registration Year,Role (member/treasurer/admin),Savings Slots (1-10),Next of Kin Name,Next of Kin Phone,Next of Kin Relationship,Next of Kin Address\n" .
            "MEM-2026-999,Updated Name,existing@example.com,08000000000,Address,2026,member,1,Kin,080,Rel,Addr\n";

        $file = UploadedFile::fake()->createWithContent('members.csv', $csvContent);

        $this->actingAs($this->admin)->post('/admin/batch-upload/preview', [
            'import_type' => 'members',
            'duplicate_mode' => 'skip',
            'import_file' => $file,
        ]);

        $this->actingAs($this->admin)->post('/admin/batch-upload/confirm');

        $user = User::where('email', 'existing@example.com')->first();
        $this->assertEquals('Original Name', $user->name); // Was skipped
    }

    public function test_non_existent_member_reference_is_rejected(): void
    {
        $csvContent = "\xEF\xBB\xBF" . "Member Code,Current Slot No,Month (YYYY-MM),Amount (NGN),Payment Date (YYYY-MM-DD)\n" .
            "MEM-DOES-NOT-EXIST,1,2026-07,5000.00,2026-07-10\n";

        $file = UploadedFile::fake()->createWithContent('savings.csv', $csvContent);

        $response = $this->actingAs($this->admin)->post('/admin/batch-upload/preview', [
            'import_type' => 'savings',
            'duplicate_mode' => 'skip',
            'import_file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertSee('Invalid');
        $response->assertSee('No member found matching Member Code');
    }

    public function test_error_report_csv_download(): void
    {
        $batchImport = BatchImport::create([
            'user_id' => $this->admin->id,
            'import_type' => 'members',
            'original_filename' => 'bad_file.csv',
            'duplicate_mode' => 'skip',
            'total_rows' => 1,
            'successful_rows' => 0,
            'duplicate_rows' => 0,
            'invalid_rows' => 1,
            'status' => 'completed',
            'error_details' => [
                [
                    'row_number' => 2,
                    'identifier' => 'bad_email',
                    'issue' => 'Validation Error',
                    'reason' => 'Valid Email address is required.',
                ]
            ],
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/batch-upload/error-report/' . $batchImport->id);
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertSee('Valid Email address is required.');
    }

    public function test_bulk_import_expenses(): void
    {
        $csvContent = "\xEF\xBB\xBF" . "Category,Description,Amount (NGN),Expense Date (YYYY-MM-DD),Status (approved/pending/rejected)\n" .
            "administrative,Stationery and printer ink,15000.00,2026-07-10,approved\n";

        $file = UploadedFile::fake()->createWithContent('expenses.csv', $csvContent);

        $this->actingAs($this->admin)->post('/admin/batch-upload/preview', [
            'import_type' => 'expenses',
            'duplicate_mode' => 'skip',
            'import_file' => $file,
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/batch-upload/confirm');
        $response->assertRedirect('/admin/batch-upload');

        $expense = \App\Models\Expense::where('category', 'administrative')->first();
        $this->assertNotNull($expense);
        $this->assertEquals(15000.00, $expense->amount);
    }

    public function test_bulk_import_investments(): void
    {
        $csvContent = "\xEF\xBB\xBF" . "Investment Name,Type Slug,Description,Capital Amount (NGN),Start Date (YYYY-MM-DD),Status (active/completed/pending)\n" .
            "Real Estate Venture Alpha,real-estate,Acquisition of commercial plot,5000000.00,2026-07-01,active\n";

        $file = UploadedFile::fake()->createWithContent('investments.csv', $csvContent);

        $this->actingAs($this->admin)->post('/admin/batch-upload/preview', [
            'import_type' => 'investments',
            'duplicate_mode' => 'skip',
            'import_file' => $file,
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/batch-upload/confirm');
        $response->assertRedirect('/admin/batch-upload');

        $inv = \App\Models\Investment::where('name', 'Real Estate Venture Alpha')->first();
        $this->assertNotNull($inv);
        $this->assertEquals(5000000.00, $inv->capital_amount);
    }

    public function test_bulk_import_registration_fees(): void
    {
        $memberUser = User::factory()->create([
            'email' => 'regfeeuser@example.com',
            'member_code' => 'MEM-2026-888',
        ]);

        $csvContent = "\xEF\xBB\xBF" . "Member Code,Fee Amount (NGN),Payment Date (YYYY-MM-DD),Payment Method,Reference Number\n" .
            "MEM-2026-888,1000.00,2026-07-01,Cash,BULK-REF-100\n";

        $file = UploadedFile::fake()->createWithContent('registration_fees.csv', $csvContent);

        $this->actingAs($this->admin)->post('/admin/batch-upload/preview', [
            'import_type' => 'registration_fees',
            'duplicate_mode' => 'skip',
            'import_file' => $file,
        ]);

        $response = $this->actingAs($this->admin)->post('/admin/batch-upload/confirm');
        $response->assertRedirect('/admin/batch-upload');

        $feePayment = \App\Models\RegistrationFeePayment::where('reference_number', 'BULK-REF-100')->first();
        $this->assertNotNull($feePayment);
        $this->assertEquals(1000.00, $feePayment->amount);
    }
}
