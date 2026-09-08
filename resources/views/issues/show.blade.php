@php
    $typeLabels = ['task' => 'Task', 'bug' => 'Bug'];
    $statusLabels = ['open' => 'Chưa làm', 'in_progress' => 'Đang làm', 'done' => 'Hoàn thành'];
    $priorityLabels = ['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
    $severityLabels = ['low' => 'Thấp', 'medium' => 'Trung bình', 'high' => 'Cao', 'critical' => 'Nghiêm trọng'];

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

    $formatBytes = function (?int $bytes): string {
        if ($bytes === null) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), 1).' '.$units[$power];
    };
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
        <div class="flex w-full flex-col gap-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <a
                    href="{{ route('issues.index', $project['id']) }}"
                    class="text-sm font-medium text-on-surface-variant transition hover:text-on-surface"
                >
                    ← Tất cả issue
                </a>

                @can('update', $issue)
                    <a
                        href="{{ route('issues.edit', [$project['id'], $issue->id]) }}"
                        class="rounded-full border border-outline px-4 py-1.5 text-sm font-medium text-primary transition hover:bg-primary/8"
                    >
                        Sửa
                    </a>
                @endcan
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Main column --}}
                <div class="flex flex-col gap-6 lg:col-span-2">
                    <section class="rounded-3xl glass p-6">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm text-on-surface-variant">#{{ $issue->id }}</span>
                            <span class="{{ $badgeBase }} {{ $typeBadges[$issue->type->value] }}">
                                {{ $typeLabels[$issue->type->value] }}
                            </span>
                            <span class="{{ $badgeBase }} {{ $statusBadges[$issue->status->value] }}">
                                {{ $statusLabels[$issue->status->value] }}
                            </span>
                            @if ($issue->is_private)
                                <span class="{{ $badgeBase }} bg-surface-container-high text-on-surface-variant">Riêng tư</span>
                            @endif
                        </div>

                        <h1 class="mt-3 text-2xl font-semibold text-on-surface">{{ $issue->title }}</h1>

                        <p class="mt-2 text-xs text-on-surface-variant">
                            Tạo {{ $issue->created_at->diffForHumans() }}
                            @if ($issue->reporter)
                                bởi {{ $issue->reporter->name }}
                            @endif
                            · Cập nhật {{ $issue->updated_at->diffForHumans() }}
                        </p>

                        @if ($issue->parent)
                            <p class="mt-3 text-sm text-on-surface-variant">
                                Task cha:
                                <a href="{{ route('issues.show', [$project['id'], $issue->parent->id]) }}" class="font-medium text-primary hover:underline">
                                    #{{ $issue->parent->id }} {{ $issue->parent->title }}
                                </a>
                            </p>
                        @endif
                    </section>

                    <section class="rounded-3xl glass p-6">
                        <h3 class="mb-3 font-medium text-on-surface">Mô tả</h3>
                        @if (filled($issue->description))
                            <p class="whitespace-pre-wrap text-sm text-on-surface">{{ $issue->description }}</p>
                        @else
                            <p class="text-sm text-on-surface-variant">Issue này chưa có mô tả.</p>
                        @endif
                    </section>

                    <section class="rounded-3xl glass p-6">
                        <h3 class="mb-3 font-medium text-on-surface">
                            Tệp đính kèm
                            <span class="text-sm font-normal text-on-surface-variant">({{ $issue->attachments->count() }})</span>
                        </h3>
                        @if ($issue->attachments->isEmpty())
                            <p class="text-sm text-on-surface-variant">Không có tệp đính kèm.</p>
                        @else
                            <ul class="flex flex-col divide-y divide-outline-variant">
                                @foreach ($issue->attachments as $attachment)
                                    <li class="flex flex-wrap items-center justify-between gap-2 py-2 text-sm">
                                        <span class="font-medium text-on-surface">{{ $attachment->original_name }}</span>
                                        <span class="text-xs text-on-surface-variant">
                                            {{ $formatBytes($attachment->size) }}
                                            @if ($attachment->uploader)
                                                · {{ $attachment->uploader->name }}
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>

                    <section class="rounded-3xl glass p-6">
                        <h3 class="mb-3 font-medium text-on-surface">
                            Task con
                            <span class="text-sm font-normal text-on-surface-variant">({{ $issue->children->count() }})</span>
                        </h3>
                        @if ($issue->children->isEmpty())
                            <p class="text-sm text-on-surface-variant">Không có task con.</p>
                        @else
                            <ul class="flex flex-col divide-y divide-outline-variant">
                                @foreach ($issue->children as $child)
                                    <li class="flex flex-wrap items-center justify-between gap-2 py-2 text-sm">
                                        <a href="{{ route('issues.show', [$project['id'], $child->id]) }}" class="font-medium text-primary hover:underline">
                                            #{{ $child->id }} {{ $child->title }}
                                        </a>
                                        <span class="flex items-center gap-2">
                                            <span class="{{ $badgeBase }} {{ $statusBadges[$child->status->value] }}">
                                                {{ $statusLabels[$child->status->value] }}
                                            </span>
                                            @if ($child->percent_done > 0)
                                                <span class="text-xs text-on-surface-variant">{{ $child->percent_done }}%</span>
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                </div>

                {{-- Sidebar column --}}
                <div class="flex flex-col gap-4">
                    <section class="rounded-3xl glass p-6">
                        <dl class="flex flex-col gap-4 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-on-surface-variant">Người thực hiện</dt>
                                <dd class="font-medium text-on-surface">{{ $issue->assignee?->name ?? 'Chưa gán' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-on-surface-variant">Ưu tiên</dt>
                                <dd class="text-xs {{ $priorityBadges[$issue->priority->value] }}">{{ $priorityLabels[$issue->priority->value] }}</dd>
                            </div>
                            @if ($issue->type->value === 'bug' && $issue->severity)
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-on-surface-variant">Mức độ nghiêm trọng</dt>
                                    <dd class="font-medium text-on-surface">{{ $severityLabels[$issue->severity->value] }}</dd>
                                </div>
                            @endif
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-on-surface-variant">Ngày bắt đầu</dt>
                                <dd class="text-on-surface">{{ $issue->start_date?->format('d/m/Y') ?? '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-on-surface-variant">Hạn hoàn thành</dt>
                                <dd class="text-on-surface">{{ $issue->due_date?->format('d/m/Y') ?? '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-on-surface-variant">Ước lượng</dt>
                                <dd class="text-on-surface">{{ $issue->estimated_hours ? $issue->estimated_hours.' giờ' : '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-on-surface-variant">Danh mục</dt>
                                <dd class="text-on-surface">{{ $issue->category ?? '—' }}</dd>
                            </div>
                        </dl>

                        <div class="mt-4">
                            <div class="flex items-center justify-between text-xs text-on-surface-variant">
                                <span>% Hoàn thành</span>
                                <span class="font-semibold text-on-surface">{{ $issue->percent_done }}%</span>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-surface-container-high">
                                <div class="h-full rounded-full bg-primary" style="width: {{ $issue->percent_done }}%"></div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-3xl glass p-6">
                        <h3 class="mb-3 font-medium text-on-surface">
                            Người theo dõi
                            <span class="text-sm font-normal text-on-surface-variant">({{ $issue->watchers->count() }})</span>
                        </h3>
                        @if ($issue->watchers->isEmpty())
                            <p class="text-sm text-on-surface-variant">Chưa có người theo dõi.</p>
                        @else
                            <div class="flex flex-wrap gap-2">
                                @foreach ($issue->watchers as $watcher)
                                    <span class="rounded-full border border-outline-variant px-3 py-1 text-sm text-on-surface">
                                        {{ $watcher->name }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
