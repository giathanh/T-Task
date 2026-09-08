<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4">
            <h2 class="text-xl font-medium leading-tight text-on-surface">{{ $project->name }}</h2>
            <x-project-nav :project="$project" active="activity" />
        </div>
    </x-slot>

    @php
        $types = ['task' => 'Task', 'bug' => 'Bug', 'wiki' => 'Wiki'];
        $badgeClasses = [
            'task' => 'bg-primary-container text-on-primary-container',
            'bug' => 'bg-error-container text-on-error-container',
            'wiki' => 'bg-tertiary-container text-on-tertiary-container',
        ];
    @endphp

    <div class="py-8 sm:py-12">
        <div class="flex w-full flex-col gap-8 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex flex-col gap-2">
                    <p class="text-xs font-semibold uppercase tracking-widest text-primary">Nhịp làm việc của dự án</p>
                    <h1 class="text-3xl font-semibold tracking-tight text-on-surface">Activity</h1>
                    <p class="max-w-xl text-sm leading-6 text-on-surface-variant">Theo dõi các Task, Bug và trang Wiki được tạo trong dự án.</p>
                </div>
                <a href="{{ route('issues.create', $project) }}" class="inline-flex items-center gap-2 rounded-full bg-primary px-5 py-3 text-sm font-medium text-on-primary transition hover:shadow-elevation-1 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary">
                    <span aria-hidden="true">＋</span> Tạo issue
                </a>
            </div>

            <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">
                <section aria-label="Dòng thời gian hoạt động" class="min-w-0 overflow-hidden rounded-3xl bg-surface-container-lowest shadow-elevation-1">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-outline-variant p-5 sm:p-6">
                        <nav aria-label="Lọc loại hoạt động" class="flex flex-wrap gap-2">
                            @foreach (['' => 'Tất cả', ...$types] as $key => $label)
                                <a href="{{ route('projects.activity', ['project' => $project, 'type' => $key ?: null]) }}"
                                    @if (($type ?? '') === $key) aria-current="true" @endif
                                    @class(['rounded-full px-4 py-2 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-primary', 'bg-secondary-container text-on-secondary-container' => ($type ?? '') === $key, 'text-on-surface-variant hover:bg-on-surface/8' => ($type ?? '') !== $key])>
                                    {{ $label }}
                                </a>
                            @endforeach
                        </nav>
                        <span class="text-xs text-on-surface-variant">Mới nhất trước</span>
                    </div>

                    <div class="p-5 sm:p-8">
                        @forelse ($activities->getCollection()->groupBy(fn ($activity) => $activity->date->toDateString()) as $date => $entries)
                            <section class="mb-8 last:mb-0" aria-label="{{ $entries->first()->date->format('d/m/Y') }}">
                                <div class="mb-5 flex items-center gap-3">
                                    <h3 class="text-sm font-semibold text-on-surface">{{ $entries->first()->date->isToday() ? 'Hôm nay' : ($entries->first()->date->isYesterday() ? 'Hôm qua' : $entries->first()->date->format('d/m/Y')) }}</h3>
                                    <span class="rounded-full bg-surface-container px-2 py-0.5 text-xs text-on-surface-variant">{{ $entries->count() }}</span>
                                    <div class="h-px flex-1 bg-outline-variant" aria-hidden="true"></div>
                                </div>
                                <ol class="flex flex-col">
                                    @foreach ($entries as $activity)
                                        <li class="group relative flex gap-4 pb-6 last:pb-0">
                                            <div class="absolute bottom-0 left-5 top-10 w-px bg-outline-variant group-last:hidden" aria-hidden="true"></div>
                                            <span class="relative flex size-10 shrink-0 items-center justify-center rounded-full bg-secondary-container text-sm font-semibold text-on-secondary-container" aria-hidden="true">{{ mb_strtoupper(mb_substr($activity->author ?? '?', 0, 1)) }}</span>
                                            <div class="flex min-w-0 flex-1 flex-col gap-2">
                                                <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1 text-sm">
                                                    <p class="break-words text-on-surface-variant"><span class="font-medium text-on-surface">{{ $activity->author ?? 'Người tạo chưa xác định' }}</span> đã tạo {{ $types[$activity->type] }}</p>
                                                    <time datetime="{{ $activity->date->toIso8601String() }}" title="{{ $activity->date->format('d/m/Y H:i') }}" class="text-xs text-on-surface-variant">{{ $activity->date->format('H:i') }}</time>
                                                </div>
                                                <a href="{{ $activity->url }}" class="flex items-center gap-3 rounded-2xl border border-outline-variant p-4 transition hover:border-primary hover:bg-primary/5 focus-visible:outline-2 focus-visible:outline-primary">
                                                    <span class="shrink-0 rounded-lg px-2 py-1 text-xs font-medium {{ $badgeClasses[$activity->type] }}">{{ $types[$activity->type] }}</span>
                                                    <span class="min-w-0 flex-1 break-words text-sm font-medium text-on-surface">@if ($activity->type !== 'wiki')<span class="text-on-surface-variant">#{{ $activity->id }}</span> @endif{{ $activity->title }}</span>
                                                    <span aria-hidden="true" class="text-on-surface-variant">↗</span>
                                                </a>
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>
                            </section>
                        @empty
                            <div class="flex flex-col items-center gap-3 py-12 text-center">
                                <span class="flex size-14 items-center justify-center rounded-2xl bg-secondary-container text-2xl text-on-secondary-container" aria-hidden="true">◷</span>
                                <h3 class="text-lg font-medium text-on-surface">Chưa có hoạt động{{ $type ? ' '.$types[$type] : '' }}</h3>
                                <p class="max-w-sm text-sm leading-6 text-on-surface-variant">Các nội dung mới sẽ xuất hiện tại đây khi thành viên tạo Task, Bug hoặc trang Wiki.</p>
                                <a href="{{ route($type === 'wiki' ? 'projects.wiki.create' : 'issues.create', $project) }}" class="mt-2 rounded-full bg-primary px-5 py-2.5 text-sm font-medium text-on-primary">{{ $type === 'wiki' ? 'Tạo trang Wiki' : 'Tạo issue đầu tiên' }}</a>
                            </div>
                        @endforelse
                    </div>
                    @if ($activities->hasPages())
                        <nav aria-label="Phân trang hoạt động" class="flex items-center justify-between gap-3 border-t border-outline-variant p-5 text-sm">
                            @if ($activities->previousPageUrl())<a class="rounded-full px-4 py-2 text-primary hover:bg-primary/8" href="{{ $activities->previousPageUrl() }}">← Trang trước</a>@else<span></span>@endif
                            <span class="text-on-surface-variant">{{ $activities->currentPage() }} / {{ $activities->lastPage() }}</span>
                            @if ($activities->nextPageUrl())<a class="rounded-full px-4 py-2 text-primary hover:bg-primary/8" href="{{ $activities->nextPageUrl() }}">Trang sau →</a>@else<span></span>@endif
                        </nav>
                    @endif
                </section>

                <aside class="flex flex-col gap-5">
                    <section class="rounded-3xl bg-surface-container-lowest p-6 shadow-elevation-1">
                        <h3 class="font-medium text-on-surface">Trong dự án</h3>
                        <p class="mt-4 text-4xl font-semibold tracking-tight text-on-surface">{{ $counts->sum() }}</p>
                        <p class="mt-1 text-sm text-on-surface-variant">nội dung đã được tạo</p>
                        <dl class="mt-6 flex flex-col gap-4 border-t border-outline-variant pt-5">
                            @foreach ($types as $key => $label)
                                <div class="flex items-center justify-between text-sm"><dt class="flex items-center gap-3 text-on-surface-variant"><span class="size-2 rounded-full {{ $badgeClasses[$key] }}" aria-hidden="true"></span>{{ $label }}</dt><dd class="font-semibold text-on-surface">{{ $counts[$key] ?? 0 }}</dd></div>
                            @endforeach
                        </dl>
                    </section>
                    <div class="rounded-3xl bg-secondary-container/50 p-6">
                        <h3 class="text-sm font-medium text-on-surface">Cùng nắm bắt tiến độ</h3>
                        <p class="mt-2 text-sm leading-6 text-on-surface-variant">Mở một hoạt động để xem chi tiết và tiếp tục công việc cùng nhóm.</p>
                        <p class="mt-4 text-xs leading-5 text-on-surface-variant">Dòng thời gian ghi nhận thời điểm tạo nội dung hiện có, chưa bao gồm lịch sử chỉnh sửa hoặc xóa.</p>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
