<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4">
            <h2 class="text-xl font-medium leading-tight text-on-surface">{{ $project->name }}</h2>
            <x-project-nav :project="$project" active="calendar" />
        </div>
    </x-slot>

    <div class="py-8 sm:py-12">
        <div class="flex w-full flex-col gap-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="flex flex-col gap-2">
                    <p class="text-xs font-semibold uppercase tracking-widest text-primary">Kế hoạch của dự án</p>
                    <h1 class="text-3xl font-semibold tracking-tight text-on-surface">Calendar</h1>
                    <p class="text-sm leading-6 text-on-surface-variant">Theo dõi ngày bắt đầu và hạn hoàn thành của Task, Bug trong dự án.</p>
                </div>
                <a href="{{ route('issues.create', $project) }}" class="inline-flex items-center gap-2 rounded-full bg-primary px-5 py-3 text-sm font-medium text-on-primary transition hover:shadow-elevation-1 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary"><span aria-hidden="true">＋</span> Tạo issue</a>
            </div>

            <section aria-label="Lịch công việc" class="overflow-hidden rounded-3xl bg-surface-container-lowest shadow-elevation-1">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-outline-variant p-5 sm:p-6">
                    <div class="flex flex-wrap items-center gap-4">
                        <h2 class="text-xl font-semibold text-on-surface">Tháng {{ $month->format('m / Y') }}</h2>
                        <nav aria-label="Chuyển tháng" class="flex items-center gap-1">
                            <a aria-label="Tháng trước" href="{{ route('projects.calendar', ['project' => $project, 'month' => $month->subMonth()->format('Y-m')]) }}" class="flex size-10 items-center justify-center rounded-full text-on-surface-variant hover:bg-on-surface/8">←</a>
                            <a href="{{ route('projects.calendar', $project) }}" class="rounded-full border border-outline px-4 py-2 text-sm font-medium text-primary hover:bg-primary/8">Hôm nay</a>
                            <a aria-label="Tháng sau" href="{{ route('projects.calendar', ['project' => $project, 'month' => $month->addMonth()->format('Y-m')]) }}" class="flex size-10 items-center justify-center rounded-full text-on-surface-variant hover:bg-on-surface/8">→</a>
                        </nav>
                    </div>
                    <form method="GET" action="{{ route('projects.calendar', $project) }}" class="flex flex-wrap items-center gap-3">
                        <label for="calendar-month" class="text-sm text-on-surface-variant">Chọn tháng</label>
                        <input id="calendar-month" name="month" type="month" value="{{ $month->format('Y-m') }}" required class="rounded-xl border border-outline bg-surface-container-lowest px-3 py-2 text-sm text-on-surface focus:outline-primary">
                        <button class="rounded-full bg-secondary-container px-4 py-2 text-sm font-medium text-on-secondary-container">Xem</button>
                        <x-input-error :messages="$errors->get('month')" />
                    </form>
                </div>
                <div class="flex flex-wrap items-center gap-4 px-5 py-4 text-xs text-on-surface-variant">
                    <span class="rounded-lg bg-primary-container px-3 py-1 text-on-primary-container">Task</span>
                    <span class="rounded-lg bg-error-container px-3 py-1 text-on-error-container">Bug</span>
                    <span>Nhấn vào một mốc lịch để xem issue. ✓ Đã hoàn thành.</span>
                </div>
                <div class="overflow-x-auto" tabindex="0" aria-label="Lịch tháng, cuộn ngang trên màn hình nhỏ">
                    <table class="w-full min-w-[840px] table-fixed border-collapse">
                        <thead>
                            <tr>
                                @foreach (['Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy', 'Chủ nhật'] as $weekday)
                                    <th scope="col" class="border-y border-outline-variant bg-surface-container-low px-3 py-3 text-left text-xs font-medium text-on-surface-variant">{{ $weekday }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (array_chunk($days, 7) as $week)
                                <tr>
                                    @foreach ($week as $day)
                                        <td @class(['h-36 border border-outline-variant p-2 align-top', 'bg-surface-container-low' => !$day->isSameMonth($month)])>
                                            <time datetime="{{ $day->toDateString() }}" @if ($day->isToday()) aria-current="date" @endif @class(['mb-2 inline-flex size-8 items-center justify-center rounded-full text-sm', 'bg-primary font-semibold text-on-primary' => $day->isToday(), 'text-on-surface' => !$day->isToday() && $day->isSameMonth($month), 'text-on-surface-variant' => !$day->isToday() && !$day->isSameMonth($month)])>{{ $day->day }}</time>
                                            <div class="flex flex-col gap-1.5">
                                                @foreach ($events[$day->toDateString()] ?? [] as $event)
                                                    <a href="{{ route('issues.show', [$project, $event['issue']]) }}" @class(['block rounded-lg px-2 py-1.5 text-xs leading-5 transition hover:shadow-elevation-1 focus-visible:outline-2 focus-visible:outline-primary', 'bg-primary-container text-on-primary-container' => $event['issue']->type->value === 'task', 'bg-error-container text-on-error-container' => $event['issue']->type->value === 'bug'])>
                                                        <span class="block font-semibold">{{ $event['label'] }} @if ($event['issue']->status->value === 'done')<span aria-label="Đã hoàn thành">✓</span>@endif</span>
                                                        <span class="block break-words">#{{ $event['issue']->id }} {{ $event['issue']->title }}</span>
                                                    </a>
                                                @endforeach
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-outline-variant p-5 text-sm leading-6 text-on-surface-variant">
                    @if (empty($events))
                        <p class="font-medium text-on-surface">Chưa có mốc công việc trong khoảng lịch này.</p>
                    @endif
                    <p>Issue chưa có ngày bắt đầu hoặc hạn hoàn thành sẽ không xuất hiện trên lịch. Bạn có thể cập nhật ngày trong <a href="{{ route('issues.index', $project) }}" class="font-medium text-primary underline underline-offset-4">danh sách Issues</a>.</p>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
