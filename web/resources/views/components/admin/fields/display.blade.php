@props(['value', 'role' => null])

<input
    class="ui-input"
    type="text"
    value="{{ $value }}"
    readonly
    @if ($role === 'birth-estimate') data-birth-estimate-preview @else data-current-pen-preview @endif
>
