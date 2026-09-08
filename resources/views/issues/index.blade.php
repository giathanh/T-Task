@php
    $typeLabels = ['task' => 'Task', 'bug' => 'Bug'];
    $statusLabels = ['open' => 'Chưa làm', 'in_progress' => 'Đang làm', 'done' => 'Hoàn thành'];
    $priorityLabels = ['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];

    $typeBadges = [
        'task' => 'bg-secondary-container text-on-secondary-container',
        'bug' => 'bg-error-container text-on-error-container',
    ];
    $statusBadges = [
        'open' => 'bg-surface-container-high text-on-surface-variant',
        'in_progress' => 'bg-primary-container text-on-primary-container',
        'done' => 'bg-tertiary-container text-on-tertiary-container',
    ];
    $badgeBase = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium';

    $priorityBadges = [
        'low' => 'text-xs text-on-surface-variant',
        'normal' => 'text-xs text-on-surface-variant',
        'high' => 'text-xs font-medium text-tertiary',
        'urgent' => $badgeBase.' bg-error text-on-error',
    ];
    $priorityRows = [
        'low' => 'hover:bg-on-surface/8',
        'normal' => 'hover:bg-on-surface/8',
        'high' => 'bg-priority-high-container hover:bg-priority-high-container-hover',
        'urgent' => 'bg-priority-urgent-container hover:bg-priority-urgent-container-hover',
    ];
    $hasFilters = collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
    $showFilters = $hasFilters || $issueCount > 0;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4">
            <h2 class="text-xl font-medium leading-tight text-on-surface">
                Issues — {{ $project['name'] }}
            </h2>

            <x-project-nav :project="$project" active="issues" />
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-3xl bg-surface-container-lowest shadow-elevation-1">
                <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-4 sm:px-6 sm:py-5">
                    <h3 class="font-medium text-on-surface">
                        {{ $issueCount }} issue{{ $issueCount === 1 ? '' : 's' }}
                        @if ($hasFilters)
                            <span class="text-sm font-normal text-on-surface-variant">khớp bộ lọc</span>
                        @endif
                    </h3>
                    <p class="text-xs text-on-surface-variant">Cha trước, con bên dưới · 20 nhóm mỗi trang</p>

                    <a
                        href="{{ $newIssueUrl }}"
                        class="inline-flex h-10 items-center gap-2 rounded-full bg-primary px-5 text-sm font-medium text-on-primary shadow-elevation-1 transition hover:shadow-elevation-2 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                    >
                        <span aria-hidden="true">＋</span> Issue mới
                    </a>
                </div>

                @if ($showFilters)
                    <form
                        method="GET"
                        action="{{ route('issues.index', $project['id']) }}"
                        class="flex flex-wrap items-end gap-3 border-t border-outline-variant px-4 py-4 sm:px-6 sm:py-5"
                    >
                        <div class="min-w-48 flex-1">
                            <x-md3-text-field name="q" label="Tìm theo tiêu đề" :value="$filters['q'] ?? null" />
                        </div>
                        <div class="w-40">
                            <x-md3-select
                                name="type"
                                label="Loại"
                                placeholder="Tất cả"
                                :value="$filters['type'] ?? null"
                                :options="collect($typeLabels)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()"
                            />
                        </div>
                        <div class="w-44">
                            <x-md3-select
                                name="status"
                                label="Trạng thái"
                                placeholder="Tất cả"
                                :value="$filters['status'] ?? null"
                                :options="collect($statusLabels)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()"
                            />
                        </div>
                        <div class="w-48">
                            <x-md3-select
                                name="assignee"
                                label="Người thực hiện"
                                placeholder="Tất cả"
                                :value="$filters['assignee'] ?? null"
                                :options="$members->map(fn ($m) => ['value' => $m['id'], 'label' => $m['name']])"
                            />
                        </div>
                        <div class="flex gap-2">
                            <x-primary-button>Lọc</x-primary-button>
                            @if ($hasFilters)
                                <a href="{{ route('issues.index', $project['id']) }}">
                                    <x-secondary-button type="button">Xoá lọc</x-secondary-button>
                                </a>
                            @endif
                        </div>
                    </form>
                @endif

                @if ($issues->isEmpty())
                    <div class="border-t border-outline-variant px-6 py-16 text-center">
                        <p class="text-on-surface-variant">
                            {{ $hasFilters ? 'Không có issue nào khớp bộ lọc.' : 'Dự án chưa có issue nào.' }}
                        </p>
                        @unless ($hasFilters)
                            <a
                                href="{{ $newIssueUrl }}"
                                class="mt-4 inline-flex rounded-full bg-primary px-4 py-1.5 text-sm font-medium text-on-primary"
                            >
                                Tạo issue đầu tiên
                            </a>
                        @endunless
                    </div>
                @else
                    <ul class="divide-y divide-outline-variant border-t border-outline-variant lg:hidden">
                        @foreach ($issues as $issue)
                            <li>
                                <a
                                    href="{{ route('issues.show', [$project['id'], $issue->id]) }}"
                                    class="block px-4 py-4 transition-colors {{ $priorityRows[$issue->priority->value] }}"
                                    style="padding-inline-start: {{ 1 + min($depths[$issue->id], 6) * 1.25 }}rem"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <span class="font-medium text-on-surface">
                                            @if ($depths[$issue->id] > 0)
                                                <span aria-hidden="true" class="text-on-surface-variant">↳</span>
                                            @endif
                                            {{ $issue->title }}
                                        </span>
                                        <span class="shrink-0 pt-0.5 text-xs tabular-nums text-on-surface-variant">#{{ $issue->id }}</span>
                                    </div>
                                    @if ($issue->parent_id)
                                        <p class="mt-1 text-xs text-on-surface-variant">Issue cha #{{ $issue->parent_id }}</p>
                                    @endif
                                    <div class="mt-2 flex flex-wrap items-center gap-2">
                                        <span class="{{ $badgeBase }} {{ $typeBadges[$issue->type->value] }}">
                                            {{ $typeLabels[$issue->type->value] }}
                                        </span>
                                        <span class="{{ $badgeBase }} {{ $statusBadges[$issue->status->value] }}">
                                            {{ $statusLabels[$issue->status->value] }}
                                        </span>
                                        <span class="{{ $priorityBadges[$issue->priority->value] }}">
                                            {{ $priorityLabels[$issue->priority->value] }}
                                        </span>
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-on-surface-variant">
                                        <span>{{ $issue->assignee?->name ?? 'Chưa gán' }}</span>
                                        <span>Hạn {{ $issue->due_date?->format('d/m/Y') ?? '—' }}</span>
                                        <span class="tabular-nums">{{ $issue->percent_done }}% done</span>
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="hidden overflow-x-auto border-t border-outline-variant lg:block">
                        <table class="w-full min-w-[58rem] text-left text-sm">
                            <thead class="border-b border-outline-variant text-xs uppercase tracking-wide text-on-surface-variant">
                                <tr>
                                    <th class="px-4 py-3 font-medium">#</th>
                                    <th class="px-4 py-3 font-medium">Tiêu đề</th>
                                    <th class="px-4 py-3 font-medium">Loại</th>
                                    <th class="px-4 py-3 font-medium">Trạng thái</th>
                                    <th class="px-4 py-3 font-medium">Ưu tiên</th>
                                    <th class="whitespace-nowrap px-4 py-3 text-right font-medium">% Done</th>
                                    <th class="px-4 py-3 font-medium">Người thực hiện</th>
                                    <th class="px-4 py-3 font-medium">Hạn</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant">
                                @foreach ($issues as $issue)
                                    <tr class="transition-colors {{ $priorityRows[$issue->priority->value] }}">
                                        <td class="px-4 py-3 tabular-nums text-on-surface-variant">{{ $issue->id }}</td>
                                        <td class="px-4 py-3" style="padding-inline-start: {{ 1 + min($depths[$issue->id], 6) * 1.25 }}rem">
                                            @if ($depths[$issue->id] > 0)
                                                <span aria-hidden="true" class="text-on-surface-variant">↳</span>
                                            @endif
                                            <a
                                                href="{{ route('issues.show', [$project['id'], $issue->id]) }}"
                                                class="font-medium text-on-surface hover:text-primary hover:underline"
                                            >
                                                {{ $issue->title }}
                                            </a>
                                            @if ($issue->parent_id)
                                                <p class="mt-1 text-xs text-on-surface-variant">Issue cha #{{ $issue->parent_id }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="{{ $badgeBase }} {{ $typeBadges[$issue->type->value] }}">
                                                {{ $typeLabels[$issue->type->value] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="{{ $badgeBase }} {{ $statusBadges[$issue->status->value] }}">
                                                {{ $statusLabels[$issue->status->value] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="{{ $priorityBadges[$issue->priority->value] }}">
                                                {{ $priorityLabels[$issue->priority->value] }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-on-surface">
                                            {{ $issue->percent_done }}%
                                        </td>
                                        <td class="px-4 py-3 text-on-surface-variant">
                                            {{ $issue->assignee?->name ?? '—' }}
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 tabular-nums text-on-surface-variant">
                                            {{ $issue->due_date?->format('d/m/Y') ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($issues->hasPages())
                        <nav
                            aria-label="Phân trang issue"
                            class="flex items-center justify-between gap-3 border-t border-outline-variant px-4 py-3 text-sm sm:px-6"
                        >
                            @if ($issues->previousPageUrl())
                                <a class="rounded-full px-4 py-2 text-primary hover:bg-primary/8" href="{{ $issues->previousPageUrl() }}">← Trang trước</a>
                            @else
                                <span></span>
                            @endif
                            <span class="text-on-surface-variant">{{ $issues->currentPage() }} / {{ $issues->lastPage() }}</span>
                            @if ($issues->nextPageUrl())
                                <a class="rounded-full px-4 py-2 text-primary hover:bg-primary/8" href="{{ $issues->nextPageUrl() }}">Trang sau →</a>
                            @else
                                <span></span>
                            @endif
                        </nav>
                    @endif
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
