{{-- Recursive account tree node. Variables: $account, $depth --}}
<li>
    <div class="ac-tree__row {{ $account->is_active ? '' : 'ac-tree__row--inactive' }}">
        <span class="ac-tree__indent" data-depth="{{ $depth }}"></span>
        <span class="ac-tree__code ac-text-mono">{{ $account->code }}</span>
        <span class="ac-tree__name">{{ $account->name }}</span>
        <span class="ac-tree__meta">
            <span class="ac-badge ac-badge--{{ $account->type }}">{{ $account->type }}</span>
        </span>
        <span class="ac-tree__actions">
            <a href="{{ route('accounting.accounts.edit', $account) }}"
               class="ac-btn ac-btn--secondary ac-btn--sm">Edit</a>

            <form method="POST"
                  action="{{ route('accounting.accounts.toggle-active', $account) }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="ac-btn ac-btn--ghost ac-btn--sm">
                    {{ $account->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
        </span>
    </div>

    @if($account->children->isNotEmpty())
        <ul class="ac-tree__children">
            @foreach($account->children as $child)
                @include('accounting._account-node', ['account' => $child, 'depth' => $depth + 1])
            @endforeach
        </ul>
    @endif
</li>
