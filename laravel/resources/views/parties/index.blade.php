@extends('layouts.app')

@php($plural = $kind.'s')
@php($isCustomer = $kind === 'customer')

@section('title', ucfirst($plural))

@section('content')
<div class="animate-fade">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
      <h2 class="h3 mb-1"><i class="bi bi-{{ $isCustomer ? 'people' : 'truck' }} me-2 text-primary"></i>{{ ucfirst($plural) }}</h2>
      <p class="text-muted small mb-0">
        {{ $isCustomer ? 'Everyone you invoice, quote or repair for.' : 'Everyone you buy from or receive bills from.' }}
        New names typed on documents are added here automatically.
      </p>
    </div>
    <a href="{{ route($plural.'.create') }}" class="btn btn-primary shadow-sm"><i class="bi bi-plus-lg me-2"></i>Add {{ ucfirst($kind) }}</a>
  </div>

  <form method="GET" class="card border-0 shadow-sm p-3 mb-4">
    <div class="row g-2 align-items-center">
      <div class="col-md">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
          <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Search name, contact, phone or email">
        </div>
      </div>
      <div class="col-md-auto">
        <select name="sort" class="form-select" onchange="this.form.submit()">
          <option value="name" @selected($sort === 'name')>Sort by name</option>
          <option value="balance" @selected($sort === 'balance')>Sort by balance owed</option>
        </select>
      </div>
      <div class="col-md-auto"><button class="btn btn-outline-primary w-100">Search</button></div>
    </div>
  </form>

  <div class="card border-0 shadow-sm overflow-hidden">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th class="ps-4">Name</th>
            <th>Contact</th>
            <th class="text-center">Documents</th>
            <th class="text-end">{{ $isCustomer ? 'Invoiced' : 'Billed' }}</th>
            <th class="text-end">Paid</th>
            <th class="text-end pe-4">{{ $isCustomer ? 'Owes you' : 'You owe' }}</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($parties as $party)
          <tr class="cursor-pointer" onclick="window.location='{{ route($plural.'.show', $party) }}'">
            <td class="ps-4">
              <a href="{{ route($plural.'.show', $party) }}" class="fw-semibold text-decoration-none">{{ $party->name }}</a>
              @if ($party->contact_person)<div class="small text-muted">{{ $party->contact_person }}</div>@endif
            </td>
            <td class="small text-muted">
              @if ($party->phone)<div><i class="bi bi-telephone me-1"></i>{{ $party->phone }}</div>@endif
              @if ($party->email)<div><i class="bi bi-envelope me-1"></i>{{ $party->email }}</div>@endif
              @if (! $party->phone && ! $party->email)<span class="fst-italic">No details</span>@endif
            </td>
            <td class="text-center"><span class="badge bg-light text-dark border">{{ (int) $party->document_count }}</span></td>
            <td class="text-end">{{ rs($party->billed) }}</td>
            <td class="text-end text-success">{{ rs($party->paid) }}</td>
            <td class="text-end pe-4 {{ $party->balance > 0 ? 'text-danger fw-bold' : 'text-muted' }}">{{ rs($party->balance) }}</td>
          </tr>
          @empty
          <tr><td colspan="6" class="text-center py-5 text-muted">
            @if ($search !== '')
              No {{ $plural }} match “{{ $search }}”.
            @else
              No {{ $plural }} yet. <a href="{{ route($plural.'.create') }}">Add the first one</a>.
            @endif
          </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @if ($parties->hasPages())
    <div class="mt-4 d-flex justify-content-center">{{ $parties->links() }}</div>
  @endif
</div>
@endsection
