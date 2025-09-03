@props([
    'editRoute' => null, // route edit
    'deleteRoute' => null, // route delete
    'id' => null, // id item
    'type' => 'default', // default, circle, compact
    'confirmMessage' => 'Are you sure you want to delete this item?',
    'editIcon' => 'ri-edit-line',
    'deleteIcon' => 'ri-delete-bin-line',
])

<div>
    {{-- Edit Button --}}
    <a href="{{ route($editRoute, $id) }}" type="button"
       class="btn btn-sm btn-outline-info me-1"
       title="Edit">
        <i class="{{ $editIcon }}"></i>
    </a>

    {{-- Delete Form --}}
    <form action="{{ route($deleteRoute, $id) }}" method="POST"
          onsubmit="return confirm('{{ $confirmMessage }}');"
          style="display:inline;">
        @csrf
        @method('DELETE')
        <button type="submit"
                class="btn btn-sm btn-outline-danger"
                title="Delete">
            <i class="{{ $deleteIcon }}"></i>
        </button>
    </form>
</div>
