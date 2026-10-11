{{-- Autocomplete list of existing customer/supplier names; link an input with list="{{ $id }}". --}}
<datalist id="{{ $id }}">
  @foreach ($names as $name)
    <option value="{{ $name }}"></option>
  @endforeach
</datalist>
