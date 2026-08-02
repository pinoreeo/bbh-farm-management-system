@props(['field', 'mode', 'values' => []])

@php
    $fieldConfig = is_array($field)
        ? $field
        : ['label' => $field, 'name' => str($field)->lower()->replace(' ', '_')->replace('&', 'dan')->toString()];
    $name = $fieldConfig['name'];
    $label = $fieldConfig['label'];
    $type = $fieldConfig['type'] ?? 'text';
    $options = $fieldConfig['options'] ?? [];
    $placeholder = $fieldConfig['placeholder'] ?? 'Masukkan ' . strtolower($label);
    $required = ($fieldConfig['required'] ?? false) || (($fieldConfig['required_on_create'] ?? false) && $mode === 'create');
    $readonly = $fieldConfig['readonly'] ?? false;
    $span = ($fieldConfig['span'] ?? null) === 2;
    $hidden = $type === 'hidden';
    $currentValue = old($name, data_get($values, $name, ''));

    if ($currentValue === '' && array_key_exists('value', $fieldConfig)) {
        $currentValue = $fieldConfig['value'];
    }

    if ($type === 'multiselect' && ! is_array($currentValue)) {
        $currentValue = array_filter((array) $currentValue);
    }

    if ($name === 'is_impor' && $currentValue !== '') {
        $currentValue = $currentValue ? 'true' : 'false';
    }

    if ($name === 'is_pregnant' && $currentValue !== '') {
        $currentValue = $currentValue ? '1' : '0';
    }

    if (in_array($type, ['date', 'time'], true) && is_string($currentValue)) {
        $currentValue = $type === 'date' ? substr($currentValue, 0, 10) : substr($currentValue, 0, 5);
    }

    $showOnEdit = $fieldConfig['show_on_edit'] ?? false;
    $hideOnEdit = $fieldConfig['hide_on_edit'] ?? false;
    $hideOnCreate = $fieldConfig['hide_on_create'] ?? false;
    $showWhen = $fieldConfig['show_when'] ?? null;
    $showWhenFilled = $fieldConfig['show_when_filled'] ?? null;
    $displayRole = $fieldConfig['display_role'] ?? null;
    $displayValue = $currentValue ?: ($fieldConfig['placeholder'] ?? '-');

    if ($displayRole === 'birth-estimate') {
        $displayValue = $currentValue ?: data_get($values, 'expected_birth_date', $fieldConfig['placeholder'] ?? '-');
    }

    $shouldRender = ! (($mode === 'create' && $hideOnCreate) || ($mode === 'edit' && $hideOnEdit) || ($showOnEdit && $mode !== 'edit'));
@endphp

@if ($shouldRender)
    @if ($hidden)
        <input type="hidden" name="{{ $name }}" value="{{ $currentValue }}">
    @else
        <label
            class="{{ $span || $type === 'textarea' ? 'md:col-span-2' : '' }} {{ $showWhen || $showWhenFilled ? 'hidden' : '' }}"
            @if ($showWhen)
                data-show-when-field="{{ array_key_first($showWhen) }}"
                data-show-when-value="{{ array_values($showWhen)[0] }}"
            @endif
            @if ($showWhenFilled)
                data-show-when-filled="{{ $showWhenFilled }}"
            @endif
        >
            <span class="ui-label">{{ $label }}</span>

            @if ($type === 'textarea')
                <x-admin.fields.textarea :name="$name" :placeholder="$placeholder" :value="$currentValue" :required="$required" :readonly="$readonly" />
            @elseif ($type === 'display')
                <x-admin.fields.display :value="$displayValue" :role="$displayRole" />
            @elseif ($type === 'select')
                <x-admin.fields.select :name="$name" :options="$options" :value="$currentValue" :field-config="$fieldConfig" :readonly="$readonly" />
            @elseif ($type === 'multiselect')
                <x-admin.fields.multiselect :name="$name" :label="$label" :options="$options" :value="$currentValue" />
            @elseif ($type === 'radio')
                <x-admin.fields.radio :name="$name" :options="$options" :value="$currentValue" :required="$required" />
            @elseif ($type === 'datalist')
                <x-admin.fields.datalist :name="$name" :options="$options" :value="$currentValue" :placeholder="$placeholder" :required="$required" :readonly="$readonly" />
            @elseif ($type === 'file')
                <x-admin.fields.file :name="$name" :accept="$fieldConfig['accept'] ?? ''" :required="$required" :mode="$mode" :values="$values" />
            @else
                <x-admin.fields.input :name="$name" :type="$type" :value="$currentValue" :placeholder="$placeholder" :step="$fieldConfig['step'] ?? ''" :required="$required" :readonly="$readonly" />
            @endif
        </label>
    @endif
@endif
