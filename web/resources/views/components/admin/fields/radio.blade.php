@props([
    'name',
    'options' => [],
    'value' => '',
    'required' => false,
])

<div class="flex min-h-10 flex-wrap items-center gap-6">
    @foreach ($options as $optionValue => $option)
        <label class="flex items-center gap-2 text-sm font-medium" style="color: var(--app-text);">
            <input type="radio" name="{{ $name }}" value="{{ $optionValue }}" @checked((string) $value === (string) $optionValue || ($value === '' && $loop->first)) @required($required)>
            <span>{{ $option }}</span>
        </label>
    @endforeach
</div>
