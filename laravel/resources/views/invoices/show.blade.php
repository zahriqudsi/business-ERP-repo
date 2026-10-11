@extends('layouts.app')

@section('title', 'Invoice #'.$invoice->invoice_no)

@push('styles')
<style>
/* custom-styles.css hides main > * when printing (meant for modal printing) */
@media print { main > .invoice-details { display: block !important; } }
</style>
@endpush

@section('content')
@include('invoices._document')
@endsection
