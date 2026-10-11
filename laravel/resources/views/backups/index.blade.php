@extends('layouts.app')

@section('title', 'Backups')

@section('content')
<div class="animate-fade">
  <div class="page-header">
    <div>
      <h1 class="h3 mb-1"><i class="bi bi-database-down me-2 text-primary"></i>Backups</h1>
      <p class="text-body-secondary small mb-0">A complete copy of your data. The newest {{ config('sjpos.backup.keep') }} are kept; change this in Settings.</p>
    </div>
    <form method="POST" action="{{ route('backups.store') }}">
      @csrf
      <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Back up now</button>
    </form>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead><tr><th class="ps-4">Backup</th><th>Made</th><th class="text-end">Size</th><th class="text-end pe-4"></th></tr></thead>
            <tbody>
              @forelse ($backups as $backup)
              <tr>
                <td class="ps-4 font-monospace small">{{ $backup['name'] }}</td>
                <td class="small"><span title="{{ $backup['created_at']->format('d M Y H:i') }}">{{ $backup['created_at']->diffForHumans() }}</span></td>
                <td class="text-end small">{{ \Illuminate\Support\Number::fileSize($backup['size'], 1) }}</td>
                <td class="text-end pe-4">
                  <div class="d-inline-flex gap-1">
                    <a href="{{ route('backups.download', $backup['name']) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-download me-1"></i>Download</a>
                    <form method="POST" action="{{ route('backups.destroy', $backup['name']) }}" onsubmit="return confirm('Delete this backup?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete backup"><i class="bi bi-trash"></i></button>
                    </form>
                  </div>
                </td>
              </tr>
              @empty
              <tr><td colspan="4" class="text-center text-body-secondary py-5">No backups yet. Click <strong>Back up now</strong> to make the first one.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><i class="bi bi-info-circle me-2"></i>Good to know</div>
        <div class="card-body small">
          <p><strong>Keep a copy elsewhere.</strong> Backups are stored on this computer, so download them regularly to a USB drive or cloud storage.</p>
          <p><strong>Automatic daily backups</strong> run when the system scheduler is set up (<code>php artisan schedule:run</code> every minute, via Windows Task Scheduler or cron).</p>
          <p class="mb-0"><strong>To restore</strong>, open phpMyAdmin, choose the database, then <em>Import</em> and pick the backup file. This replaces all current data.</p>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
