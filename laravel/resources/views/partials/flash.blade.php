@foreach (['success' => 'check-circle', 'info' => 'pencil-square', 'warning' => 'trash'] as $level => $icon)
    @if (session($level))
        <div class="alert alert-{{ $level }} alert-dismissible fade show">
            <i class="bi bi-{{ $icon }} me-2"></i>{{ session($level) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
@endforeach

@if ($errors->any() && ! $errors->has('login') && ! $errors->has('otp'))
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle me-2"></i>Please fix the following:
        <ul class="mb-0 mt-1 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
