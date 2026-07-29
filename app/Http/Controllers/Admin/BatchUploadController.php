<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BatchImport;
use App\Services\BatchImportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BatchUploadController extends Controller
{
    protected BatchImportService $importService;

    public function __construct(BatchImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Main Batch Upload Dashboard & Audit Trail
     */
    public function index()
    {
        $recentImports = BatchImport::with('user')
            ->latest()
            ->paginate(15);

        return view('admin.batch_upload.index', compact('recentImports'));
    }

    /**
     * Download Sample CSV Template
     */
    public function downloadTemplate(string $type)
    {
        if (!in_array($type, ['members', 'savings', 'running_charges', 'loans', 'expenses', 'investments', 'registration_fees'])) {
            abort(404, 'Invalid import type.');
        }

        $csvContent = $this->importService->generateTemplate($type);
        $filename = "coop_batch_template_{$type}.csv";

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Upload & Process Preview
     */
    public function preview(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
            'import_type' => 'required|string|in:members,savings,running_charges,loans,expenses,investments,registration_fees',
            'duplicate_mode' => 'required|string|in:skip,update,new_only',
        ], [
            'import_file.required' => 'Please select a CSV file to upload.',
            'import_file.mimes' => 'The uploaded file must be a CSV or Excel file (.csv, .xlsx, .xls).',
            'import_file.max' => 'File size cannot exceed 10MB.',
        ]);

        $file = $request->file('import_file');
        $type = $request->input('import_type');
        $duplicateMode = $request->input('duplicate_mode');
        $originalFilename = $file->getClientOriginalName();

        try {
            $previewData = $this->importService->parseAndValidate($file, $type, $duplicateMode);

            session([
                'batch_import_preview' => [
                    'import_type' => $type,
                    'duplicate_mode' => $duplicateMode,
                    'original_filename' => $originalFilename,
                    'preview_data' => $previewData,
                ],
            ]);

            return view('admin.batch_upload.preview', [
                'importType' => $type,
                'duplicateMode' => $duplicateMode,
                'filename' => $originalFilename,
                'preview' => $previewData,
            ]);
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['import_file' => $e->getMessage()]);
        }
    }

    /**
     * Confirm & Execute Import
     */
    public function confirmImport(Request $request)
    {
        $sessionData = session('batch_import_preview');
        if (!$sessionData) {
            return redirect()->route('admin.batch-upload.index')
                ->withErrors(['import_error' => 'No active preview session found. Please re-upload your file.']);
        }

        $type = $sessionData['import_type'];
        $duplicateMode = $sessionData['duplicate_mode'];
        $filename = $sessionData['original_filename'];
        $previewRows = $sessionData['preview_data']['rows'];

        $batchImport = $this->importService->executeImport(
            $previewRows,
            $type,
            $duplicateMode,
            auth()->id(),
            $filename
        );

        session()->forget('batch_import_preview');

        $message = "Batch Upload Completed! Processed {$batchImport->total_rows} rows: {$batchImport->successful_rows} imported successfully, {$batchImport->duplicate_rows} duplicates skipped/updated, {$batchImport->invalid_rows} rejected.";

        return redirect()->route('admin.batch-upload.index')
            ->with('success', $message);
    }

    /**
     * Download Error Report CSV for a Batch Import
     */
    public function downloadErrorReport(BatchImport $batchImport)
    {
        $csvContent = $this->importService->generateErrorReportCsv($batchImport);
        $filename = "batch_import_errors_{$batchImport->id}.csv";

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
