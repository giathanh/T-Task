@php
    $statusLabels = ['open' => 'Chưa làm', 'in_progress' => 'Đang làm', 'done' => 'Hoàn thành'];
    $statusBadges = ['open' => 'bg-surface-container-high text-on-surface-variant', 'in_progress' => 'bg-primary-container text-on-primary-container', 'done' => 'bg-tertiary-container text-on-tertiary-container'];
    $priorityLabels = ['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
    $hasFilters = collect($filters)->contains(fn ($value) => $value !== null && $value !== '');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="mb-1 text-sm text-on-surface-variant">Không gian làm việc của bạn</p>
                <h1 class="text-2xl font-medium text-on-surface">Xin chào, {{ auth()->user()->name }}</h1>
            </div>
            <time datetime="{{ $today->toDateString() }}" class="rounded-full bg-surface-container px-4 py-2 text-sm text-on-surface-variant">{{ $today->format('d/m/Y') }}</time>
        </div>
    </x-slot>

    <div class="mx-auto flex max-w-7xl flex-col gap-8 px-4 py-8 sm:px-6 lg:px-8">
        <section aria-label="Tổng quan công việc" class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-5">
            @foreach ([['total', 'Được giao cho bạn', 'bg-primary-container text-on-primary-container'], ['in_progress', 'Đang thực hiện', 'bg-surface-container-lowest text-on-surface'], ['overdue', 'Đã quá hạn', 'bg-error-container text-on-error-container'], ['done', 'Đã hoàn thành', 'bg-surface-container-lowest text-on-surface']] as [$key, $label, $colors])
                <div class="rounded-3xl p-5 sm:p-6 {{ $colors }}">
                    <p class="text-sm">{{ $label }}</p>
                    <p class="mt-3 text-4xl font-medium tabular-nums">{{ $stats[$key] }}</p>
                </div>
            @endforeach
        </section>

        <section aria-labelledby="assigned-heading" class="flex flex-col gap-5">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 id="assigned-heading" class="text-xl font-medium text-on-surface">Công việc của tôi</h2>
                    <p class="mt-1 text-sm text-on-surface-variant">Task và bug được giao cho bạn. Công việc chưa hoàn thành, gần hạn được hiển thị trước.</p>
                </div>
                <span class="rounded-full bg-secondary-container px-3 py-1 text-sm font-medium text-on-secondary-container">{{ $issues->total() }} công việc{{ $hasFilters ? ' khớp bộ lọc' : '' }}</span>
            </div>

            <form method="GET" action="{{ route('dashboard') }}" class="grid items-end gap-3 rounded-3xl bg-surface-container-lowest p-4 sm:grid-cols-2 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)_auto]">
                <x-md3-text-field name="q" label="Tìm theo tiêu đề" :value="$filters['q'] ?? null" maxlength="255" />
                <x-md3-select name="project" label="Dự án" placeholder="Tất cả dự án" :value="$filters['project'] ?? null" :options="$projects->map(fn ($project) => ['value' => $project->id, 'label' => $project->name])" />
                <x-md3-select name="status" label="Trạng thái" placeholder="Tất cả trạng thái" :value="$filters['status'] ?? null" :options="collect($statusLabels)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values()" />
                <div class="flex min-h-14 items-center gap-3">
                    <x-primary-button>Lọc</x-primary-button>
                    @if ($hasFilters)
                        <a href="{{ route('dashboard') }}" class="rounded-full px-3 py-2 text-sm font-medium text-primary hover:bg-primary/8">Xoá lọc</a>
                    @endif
                </div>
            </form>

            <div class="overflow-hidden rounded-3xl bg-surface-container-lowest shadow-elevation-1">
                @forelse ($issues as $issue)
                    @php
                        $overdue = $issue->status !== \App\Enums\IssueStatus::Done && $issue->due_date?->lt($today);
                    @endphp
                    <article class="grid gap-4 border-b border-outline-variant p-5 last:border-0 sm:p-6 lg:grid-cols-[minmax(0,1fr)_9rem_9rem_8rem] lg:items-center">
                        <div class="min-w-0">
                            <div class="mb-2 flex flex-wrap items-center gap-2 text-xs text-on-surface-variant">
                                <span class="rounded-md bg-secondary-container px-2 py-1 text-on-secondary-container">{{ $issue->type === \App\Enums\IssueType::Bug ? 'Bug' : 'Task' }}</span>
                                <span>#{{ $issue->id }}</span>
                                <span aria-hidden="true">·</span>
                                <a href="{{ route('projects.show', $issue->project_id) }}" class="break-words hover:text-primary hover:underline">{{ $issue->project->name }}</a>
                            </div>
                            <h3 class="break-words font-medium text-on-surface">
                                <a href="{{ route('issues.show', [$issue->project_id, $issue->id]) }}" class="hover:text-primary hover:underline">{{ $issue->title }}</a>
                            </h3>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 lg:flex-col lg:items-start lg:gap-2">
                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium {{ $statusBadges[$issue->status->value] }}">{{ $statusLabels[$issue->status->value] }}</span>
                            <span @class(['text-xs', 'text-error font-medium' => $issue->priority === \App\Enums\IssuePriority::Urgent, 'text-on-surface-variant' => $issue->priority !== \App\Enums\IssuePriority::Urgent])>Ưu tiên: {{ $priorityLabels[$issue->priority->value] }}</span>
                        </div>
                        <div @class(['text-sm', 'text-error' => $overdue, 'text-on-surface-variant' => ! $overdue])>
                            <p class="mb-1 text-xs">{{ $overdue ? 'Đã quá hạn' : 'Hạn hoàn thành' }}</p>
                            @if ($issue->due_date)
                                <time datetime="{{ $issue->due_date->toDateString() }}">{{ $issue->due_date->format('d/m/Y') }}</time>
                            @else
                                <span>Chưa có hạn</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 text-xs text-on-surface-variant">
                            <progress value="{{ $issue->percent_done }}" max="100" aria-label="Tiến độ công việc #{{ $issue->id }}" class="h-2 w-20 min-w-0 flex-1 accent-primary">{{ $issue->percent_done }}%</progress>
                            <span class="tabular-nums">{{ $issue->percent_done }}%</span>
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-16 text-center">
                        <div class="mx-auto mb-5 flex size-14 items-center justify-center rounded-2xl bg-primary-container text-on-primary-container">
                            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="5" y="4" width="14" height="17" rx="3"/><path d="M9 4V2h6v2M8 12l3 3 5-6"/></svg>
                        </div>
                        <h3 class="font-medium text-on-surface">{{ $hasFilters ? 'Không tìm thấy công việc phù hợp' : 'Bạn chưa được giao công việc nào' }}</h3>
                        <p class="mt-2 text-sm text-on-surface-variant">{{ $hasFilters ? 'Thử thay đổi từ khoá hoặc xoá bộ lọc để xem tất cả công việc.' : 'Các task và bug được giao cho bạn sẽ xuất hiện tại đây.' }}</p>
                    </div>
                @endforelse
            </div>
            {{ $issues->links() }}
        </section>
    </div>
</x-app-layout>
