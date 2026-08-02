@props([
    'name',
    'options' => [],
    'value' => '',
    'fieldConfig' => [],
    'readonly' => false,
])

<select class="ui-input" name="{{ $name }}" @disabled($readonly) @if (! empty($fieldConfig['depends_on'])) data-depends-on="{{ $fieldConfig['depends_on'] }}" @endif @if (! empty($fieldConfig['filter_dead_when'])) data-filter-dead-when="{{ $fieldConfig['filter_dead_when'] }}" data-filter-dead-value="{{ $fieldConfig['filter_dead_value'] }}" @endif>
    @if (! empty($fieldConfig['placeholder']))
        <option value="">{{ $fieldConfig['placeholder'] }}</option>
    @endif
    @foreach ($options as $optionKey => $option)
        @php($optionValue = (str_ends_with($name, '_id') || $name === 'key_length') ? $optionKey : (is_string($optionKey) ? $optionKey : $option))
        @php($meta = ($fieldConfig['option_meta'] ?? [])[(string) $optionValue] ?? null)
        <option
            value="{{ $optionValue }}"
            @selected((string) $value === (string) $optionValue)
            @if (is_array($meta))
                @foreach ($meta as $metaKey => $metaValue)
                    data-{{ str($metaKey)->kebab() }}="{{ $metaValue }}"
                @endforeach
            @elseif ($meta !== null)
                data-parent-value="{{ $meta }}"
                data-option-status="{{ $meta }}"
            @endif
        >{{ $option }}</option>
    @endforeach
</select>
