@php $icons = ['domain' => 'globe', 'hosting' => 'server', 'billing' => 'folder']; @endphp
<ul class="list">
    @forelse ($items as $item)
        <li>
            <span class="dot dot-{{ $item['kind'] }}"><x-icon :name="$icons[$item['kind']]"/></span>
            <div class="grow">
                <a class="title" href="{{ $item['url'] }}">{{ $item['title'] }}</a>
                <small>{{ $item['subtitle'] }}</small>
                <div><x-due :date="$item['date']"/></div>
            </div>
            <div class="right">
                <div class="num {{ $item['direction'] === 'in' ? 'in' : ($item['direction'] === 'out' ? 'out' : 'muted') }}">
                    {{ $item['direction'] === 'in' ? '+' : ($item['direction'] === 'out' ? '−' : '') }}{{ money($item['amount']) }}
                </div>
                @if ($item['action'])
                    <x-quick-action :action="$item['action']" :label="$item['action_label']" :tone="$item['direction'] === 'in' ? 'in' : 'out'"
                        :confirm="'Mark '.$item['title'].' as '.strtolower($item['action_label']).' ('.money($item['amount']).') and move the due date forward?'"/>
                @endif
            </div>
        </li>
    @empty
        <li class="muted">Nothing due. 🎉</li>
    @endforelse
</ul>
