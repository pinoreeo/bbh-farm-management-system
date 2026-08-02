@props([
    'name',
    'type' => 'text',
    'value' => '',
    'placeholder' => '',
    'step' => '',
    'required' => false,
    'readonly' => false,
])

<input class="ui-input" type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" placeholder="{{ $placeholder }}" step="{{ $step }}" @required($required) @readonly($readonly) @disabled($readonly)>
