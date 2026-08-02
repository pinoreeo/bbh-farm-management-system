@props(['name', 'placeholder', 'value', 'required' => false, 'readonly' => false])

<textarea class="ui-input min-h-28 py-3" name="{{ $name }}" placeholder="{{ $placeholder }}" @required($required) @readonly($readonly)>{{ $value }}</textarea>
