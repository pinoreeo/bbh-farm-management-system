@props([
    'name',
    'label',
    'options' => [],
    'value' => [],
])

<div class="admin-multiselect js-select-list" data-empty-label="Belum ada {{ strtolower($label) }} yang dipilih.">
    <select class="ui-input js-select-list-picker">
        <option value="">Pilih {{ $label }}</option>
        @foreach ($options as $optionKey => $option)
            @php($optionValue = str_ends_with($name, '_ids') ? $optionKey : (is_string($optionKey) ? $optionKey : $option))
            <option value="{{ $optionValue }}" @if (in_array((string) $optionValue, array_map('strval', $value), true)) data-selected="1" @endif>{{ $option }}</option>
        @endforeach
    </select>

    <div class="admin-multiselect-box hidden js-select-list-box">
        <div class="admin-multiselect-items js-select-list-items"></div>
    </div>

    <p class="mt-2 text-xs theme-muted js-select-list-empty">Belum ada {{ strtolower($label) }} yang dipilih.</p>

    <template class="js-select-list-template">
        <div class="admin-multiselect-chip">
            <span class="font-medium js-select-list-label"></span>
            <button class="admin-multiselect-remove" type="button" aria-label="Hapus pilihan">
                <x-icons name="x" class="h-3 w-3" />
            </button>
            <input type="hidden" name="{{ $name }}[]">
        </div>
    </template>
</div>
