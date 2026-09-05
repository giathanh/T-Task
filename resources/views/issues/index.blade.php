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
    $priorityBadges = [
        'low' => 'text-on-surface-variant',
        'normal' => 'text-on-surface-variant',
        'high' => 'text-tertiary',
        'urgent' => 'text-error font-semibold',
    ];

    $badgeBase = 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium';
    $hasFilters = collect($filters)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
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

    <div class="py-12">
        <div class="mx-auto flex max-w-6xl flex-col gap-6 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="font-medium text-on-surface">
                    {{ $issues->total() }} issue{{ $issues->total() === 1 ? '' : 's' }}
                    @if ($hasFilters)
                        <span class="text-sm font-normal text-on-surface-variant">khớp bộ lọc</span>
                    @endif
                </h3>

                <a
                    href="{{ $newIssueUrl }}"
                    class="rounded-full bg-primary px-4 py-1.5 text-sm font-medium text-on-primary"
                >
                    + Issue mới
                </a>
            </div>

            <form
                method="GET"
                action="{{ route('issues.index', $project['id']) }}"
                class="flex flex-wrap items-end gap-3 rounded-3xl bg-surface-container-lowest p-4 shadow-elevation-1"
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

            @if ($issues->isEmpty())
                <section class="rounded-3xl bg-surface-container-lowest p-10 text-center shadow-elevation-1">
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
                </section>
            @else
                <section class="overflow-hidden rounded-3xl bg-surface-container-lowest shadow-elevation-1">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[52rem] text-left text-sm">
                            <thead class="border-b border-outline-variant text-xs uppercase tracking-wide text-on-surface-variant">
                                <tr>
                                    <th class="px-4 py-3 font-medium">#</th>
                                    <th class="px-4 py-3 font-medium">Tiêu đề</th>
                                    <th class="px-4 py-3 font-medium">Loại</th>
                                    <th class="px-4 py-3 font-medium">Trạng thái</th>
                                    <th class="px-4 py-3 font-medium">Ưu tiên</th>
                                    <th class="px-4 py-3 font-medium">Người thực hiện</th>
                                    <th class="px-4 py-3 font-medium">Hạn</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-outline-variant">
                                @foreach ($issues as $issue)
                                    <tr class="transition hover:bg-on-surface/8">
                                        <td class="px-4 py-3 text-on-surface-variant">{{ $issue->id }}</td>
                                        <td class="px-4 py-3">
                                            <span class="font-medium text-on-surface">{{ $issue->title }}</span>
                                            @if ($issue->percent_done > 0)
                                                <span class="ml-2 text-xs text-on-surface-variant">{{ $issue->percent_done }}%</span>
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
                                            <span class="text-xs {{ $priorityBadges[$issue->priority->value] }}">
                                                {{ $priorityLabels[$issue->priority->value] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-on-surface-variant">
                                            {{ $issue->assignee?->name ?? '—' }}
                                        </td>
                                        <td class="px-4 py-3 text-on-surface-variant">
                                            {{ $issue->due_date?->format('d/m/Y') ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                {{ $issues->links() }}
            @endif
        </div>
    </div>
</x-app-layout>
