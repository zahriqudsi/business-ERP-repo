@extends('layouts.app')

@section('title', 'Quotation #'.$quotation->quotation_no)

@push('styles')
<style>
/* custom-styles.css hides main > * when printing (meant for modal printing) */
@media print { main > .invoice-details { display: block !important; } }
</style>
@endpush

@section('content')
@include('quotations._document')
@endsection
