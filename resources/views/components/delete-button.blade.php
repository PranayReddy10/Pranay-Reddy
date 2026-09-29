@props(['action', 'label' => 'Delete', 'confirm' => 'Delete this record? This cannot be undone.'])
{{--
    Delete buttons usually sit inside an edit form, and forms cannot be nested: the browser would
    merge the inner form's _method=DELETE into the edit form, turning "Save" into "Delete".
    So the delete <form> is pushed to the end of the page and the button targets it by id.
--}}
@php $formId = 'delete-'.md5($action); @endphp
<button type="submit" form="{{ $formId }}" {{ $attributes->merge(['class' => 'btn btn-danger']) }}><x-icon name="trash"/> {{ $label }}</button>
@push('detached-forms')
    <form id="{{ $formId }}" method="POST" action="{{ $action }}" hidden onsubmit="return confirm(@js($confirm))">
        @csrf @method('DELETE')
    </form>
@endpush
