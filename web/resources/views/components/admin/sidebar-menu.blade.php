@props([
    'groups' => [],
    'mobile' => false,
])

<nav class="thin-scrollbar flex-1 space-y-3 overflow-y-auto px-3 py-4">
    @foreach ($groups as $title => $items)
        <div>
            <h3 class="admin-sidebar-group mb-1.5 px-3 py-1.5 text-[10px] font-semibold uppercase leading-4">{{ $title }}</h3>
            <ul class="flex flex-col gap-1.5">
                @foreach ($items as $item)
                    @php($active = request()->routeIs($item['route']))
                    <li>
                        <a href="{{ route($item['route']) }}" class="admin-sidebar-link {{ $active ? 'admin-sidebar-link-active' : '' }}" @if($mobile) data-mobile-sidebar-link @endif>
                            <x-icons :name="$item['icon']" class="h-5 w-5 shrink-0" />
                            <span>{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
