<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\DatabaseBackup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class BackupController extends Controller
{
    public function __construct(private DatabaseBackup $backups) {}

    public function index(): View
    {
        return view('backups.index', ['backups' => $this->backups->list()]);
    }

    public function store(): RedirectResponse
    {
        try {
            $name = $this->backups->create();
        } catch (Throwable $e) {
            Log::error('Backup failed: '.$e->getMessage());

            return back()->withErrors(['backup' => 'The backup could not be created. Check that the storage folder is writable.']);
        }

        ActivityLog::record('created', 'Backup '.$name, 'backup');

        return back()->with('success', 'Backup created. Download it and keep a copy somewhere safe.');
    }

    public function download(string $file): BinaryFileResponse
    {
        $path = $this->backups->path($file);
        abort_unless($path, 404);

        return response()->download($path);
    }

    public function destroy(string $file): RedirectResponse
    {
        abort_unless($this->backups->path($file), 404);
        $this->backups->delete($file);
        ActivityLog::record('deleted', 'Backup '.$file, 'backup');

        return back()->with('warning', 'Backup deleted.');
    }
}
