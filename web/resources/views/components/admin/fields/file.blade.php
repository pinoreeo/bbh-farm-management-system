@props([
    'name',
    'accept' => '',
    'required' => false,
    'mode' => 'create',
    'values' => [],
])

<input class="ui-input py-2" type="file" name="{{ $name }}" accept="{{ $accept }}" @required($required)>
@if ($mode === 'edit' && data_get($values, 'photo_url'))
    <p class="mt-2 text-xs theme-muted">Foto saat ini sudah tersimpan. Pilih file baru untuk mengganti.</p>
@endif
