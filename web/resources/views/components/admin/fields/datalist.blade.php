@props([
    'name',
    'options' => [],
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'readonly' => false,
])

<input class="ui-input" list="{{ $name }}_options" type="text" name="{{ $name }}" value="{{ $value }}" placeholder="{{ $placeholder }}" @required($required) @readonly($readonly)>
<datalist id="{{ $name }}_options">
    @foreach ($options as $option)
        <option value="{{ $option }}"></option>
    @endforeach
</datalist>
