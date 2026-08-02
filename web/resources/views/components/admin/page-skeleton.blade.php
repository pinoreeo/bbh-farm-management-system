@props(['type' => 'table'])

<div class="admin-page-skeleton" data-page-loader hidden aria-live="polite" aria-busy="false">
    <div class="content-wrap">
        <div class="skeleton-line h-8 w-56"></div>

        @if ($type === 'dashboard')
            <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @for ($index = 0; $index < 8; $index++)
                    <div class="skeleton-card">
                        <div class="space-y-3">
                            <div class="skeleton-line h-4 w-28"></div>
                            <div class="skeleton-line h-8 w-16"></div>
                        </div>
                        <div class="mt-6 skeleton-line h-3 w-full"></div>
                    </div>
                @endfor
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
                <div class="skeleton-panel skeleton-panel-large">
                    <div class="mb-8 flex items-center justify-between gap-4">
                        <div>
                            <div class="skeleton-line h-5 w-32"></div>
                            <div class="mt-3 skeleton-line h-3 w-52"></div>
                        </div>
                        <div class="skeleton-line h-9 w-24"></div>
                    </div>
                    <div class="skeleton-chart">
                        @for ($index = 0; $index < 5; $index++)
                            <div class="skeleton-line h-px w-full"></div>
                        @endfor
                    </div>
                </div>

                <div class="grid gap-5">
                    @for ($panel = 0; $panel < 2; $panel++)
                        <div class="skeleton-panel">
                            <div class="skeleton-line h-5 w-36"></div>
                            <div class="mt-6 space-y-5">
                                @for ($index = 0; $index < 4; $index++)
                                    <div class="flex items-center gap-3">
                                        <div class="skeleton-circle h-9 w-9"></div>
                                        <div class="flex-1 space-y-2">
                                            <div class="skeleton-line h-4 w-3/4"></div>
                                            <div class="skeleton-line h-3 w-1/2"></div>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    @endfor
                </div>
            </div>

            <div class="mt-5 skeleton-panel">
                <div class="skeleton-line h-5 w-32"></div>
                <div class="mt-6 space-y-3">
                    @for ($index = 0; $index < 5; $index++)
                        <div class="grid grid-cols-[1.1fr_.7fr_.7fr_.6fr_.7fr] gap-4">
                            <div class="skeleton-line h-4 w-full"></div>
                            <div class="skeleton-line h-4 w-full"></div>
                            <div class="skeleton-line h-4 w-full"></div>
                            <div class="skeleton-line h-4 w-full"></div>
                            <div class="skeleton-line h-4 w-full"></div>
                        </div>
                    @endfor
                </div>
            </div>
        @elseif ($type === 'form')
            <div class="admin-form-shell mt-6">
                <div class="skeleton-panel">
                    <div class="skeleton-line h-5 w-44"></div>
                    <div class="mt-8 grid gap-5 md:grid-cols-2">
                        @for ($index = 0; $index < 8; $index++)
                            <div>
                                <div class="skeleton-line h-3 w-24"></div>
                                <div class="mt-2 skeleton-line h-10 w-full rounded-lg"></div>
                            </div>
                        @endfor
                    </div>
                    <div class="mt-8 flex justify-end gap-3 border-t pt-5" style="border-color: var(--app-border);">
                        <div class="skeleton-line h-9 w-24"></div>
                        <div class="skeleton-line h-9 w-28"></div>
                    </div>
                </div>
            </div>
        @elseif ($type === 'detail')
            <div class="mt-6 flex justify-between gap-3">
                <div class="skeleton-line h-9 w-24"></div>
                <div class="skeleton-line h-9 w-20"></div>
            </div>
            <div class="mt-5 skeleton-panel">
                <div class="skeleton-line h-5 w-36"></div>
                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    @for ($index = 0; $index < 8; $index++)
                        <div class="rounded-lg border border-[var(--app-border)] p-4">
                            <div class="skeleton-line h-3 w-24"></div>
                            <div class="mt-3 skeleton-line h-5 w-2/3"></div>
                        </div>
                    @endfor
                </div>
            </div>
        @else
            <div class="mt-6 admin-list-toolbar">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="skeleton-line h-9 w-full lg:max-w-md"></div>
                    <div class="flex gap-2">
                        <div class="skeleton-line h-9 w-24"></div>
                        <div class="skeleton-line h-9 w-28"></div>
                    </div>
                </div>
            </div>
            <div class="mt-5 skeleton-panel">
                <div class="skeleton-line h-5 w-36"></div>
                <div class="mt-6 space-y-3">
                    @for ($index = 0; $index < 8; $index++)
                        <div class="grid grid-cols-[1fr_.9fr_.8fr_.8fr_.7fr] gap-4">
                            <div class="skeleton-line h-4 w-full"></div>
                            <div class="skeleton-line h-4 w-full"></div>
                            <div class="skeleton-line h-4 w-full"></div>
                            <div class="skeleton-line h-4 w-full"></div>
                            <div class="skeleton-line h-4 w-full"></div>
                        </div>
                    @endfor
                </div>
            </div>
        @endif
    </div>
</div>
