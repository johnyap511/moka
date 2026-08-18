{{--
    Type-ahead select.

    Required:
      $id     unique prefix for the element ids
      $name   posted field name (goes on the hidden input)
      $items  iterable of records exposing id and name

    Optional:
      $selected        currently selected id
      $placeholder     input placeholder
      $style           inline style for the wrapper
      $required        show an error instead of submitting when nothing is picked
      $submitOnSelect  submit the surrounding form once a choice is made
      $var             assign the controller to this JS variable, for callers
                       that need to reset or drive it

    Styles and behaviour live in admin.layout so several comboboxes can share
    one implementation.
--}}
@php
    $items = collect($items)->map(function ($i) {
        return ['id' => $i->id, 'name' => $i->name];
    })->values();

    $selectedName = optional(collect($items)->firstWhere('id', $selected ?? null))['name'] ?? '';
@endphp

<div class="combo" id="{{ $id }}-combo" style="{{ $style ?? '' }}">
    <input type="text" id="{{ $id }}-search" class="form-input" autocomplete="off"
           value="{{ $selectedName }}"
           placeholder="{{ $placeholder ?? 'Type to search…' }}"
           role="combobox" aria-autocomplete="list" aria-expanded="false"
           aria-controls="{{ $id }}-list">
    <input type="hidden" name="{{ $name }}" id="{{ $id }}-value" value="{{ $selected ?? '' }}">
    <ul class="combo-list" id="{{ $id }}-list" role="listbox"></ul>
</div>
@if(!empty($required))
    <div class="form-error" id="{{ $id }}-error" style="display:none">Select an option from the list.</div>
@endif

@push('scripts')
<script>
{!! isset($var) ? 'var ' . $var . ' = ' : '' !!}window.makeCombo({
    id: @json($id),
    items: @json($items),
    required: {{ !empty($required) ? 'true' : 'false' }},
    submitOnSelect: {{ !empty($submitOnSelect) ? 'true' : 'false' }}
});
</script>
@endpush
