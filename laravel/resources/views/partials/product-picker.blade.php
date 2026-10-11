{{-- Product list for line-item pickers (see public/assets/js/product-picker.js). Expects $productOptions. --}}
<datalist id="product-list">
  @foreach ($productOptions as $option)
    <option value="{{ $option['label'] }}">{{ rs($option['price']) }}{{ $option['stock'] !== null ? ' · '.$option['stock'].' in stock' : '' }}</option>
  @endforeach
</datalist>
@push('scripts')
<script>window.SJ_PRODUCTS = @json($productOptions);</script>
<script src="{{ asset('assets/js/product-picker.js') }}"></script>
@endpush
