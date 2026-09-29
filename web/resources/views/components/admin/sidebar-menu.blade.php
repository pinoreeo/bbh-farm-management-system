@props([
    'groups' => [],
    'mobile' => false,
])

<nav class="thin-scrollbar flex-1 space-y-5 overflow-y-auto px-3 py-5">
    @foreach ($groups as $title => $items)
        <div>
            <h3 class="admin-sidebar-group mb-1 px-3 py-1.5 text-xs font-medium uppercase leading-4">{{ $title }}</h3>
            <ul class="flex flex-col gap-1">
                @foreach ($items as $item)
                    @php($active = request()->routeIs($item['route']))
                    <li>
                        <a href="{{ route($item['route']) }}" class="admin-sidebar-link {{ $active ? 'admin-sidebar-link-active' : '' }}" @if($mobile) data-mobile-sidebar-link @endif>
                            <x-icons :name="$item['icon']" class="h-6 w-6 shrink-0" />
                            <span>{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
