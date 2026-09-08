@props([
    'project',
    'issue' => null,
    'action',
    'method' => 'POST',
    'members',
    'parentOptions',
    'trackerOptions',
    'statusOptions',
    'priorityOptions',
    'severityOptions',
])

@php
    $statusLabels = ['open' => 'Chưa làm', 'in_progress' => 'Đang làm', 'done' => 'Hoàn thành'];
    $priorityLabels = ['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
    $severityLabels = ['low' => 'Thấp', 'medium' => 'Trung bình', 'high' => 'Cao', 'critical' => 'Nghiêm trọng'];

    $currentType = old('type', $issue?->type->value ?? 'task');
    $currentWatchers = collect(old('watchers', $issue?->watchers->pluck('id')->all() ?? []));
    $cancelUrl = $issue
        ? route('issues.show', [$project['id'], $issue->id])
        : route('projects.show', $project['id']);
@endphp

<form
    method="POST"
    action="{{ $action }}"
    enctype="multipart/form-data"
    x-data="{ tracker: '{{ $currentType }}', percentDone: {{ (int) old('percent_done', $issue?->percent_done ?? 0) }} }"
    class="flex flex-col gap-6"
>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div class="min-w-0 divide-y divide-outline-variant rounded-lg border border-outline-variant bg-surface-container-lowest px-4 sm:px-6">
        <section class="py-5">
            <x-md3-text-field name="title" label="Tiêu đề" :value="old('title', $issue?->title)" required autofocus />

            <div class="mt-4">
                <x-md3-textarea name="description" label="Mô tả" :value="old('description', $issue?->description)" :rows="6" />
            </div>
        </section>

        <section aria-label="Thuộc tính issue" class="grid grid-cols-1 gap-4 py-5 md:grid-cols-2">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-md3-select
                    name="type"
                    label="Tracker"
                    x-model="tracker"
                    :value="$currentType"
                    :options="collect($trackerOptions)->map(fn ($t) => ['value' => $t->value, 'label' => ucfirst($t->value)])"
                />

                <x-md3-select
                    name="status"
                    label="Status"
                    :value="old('status', $issue?->status->value ?? 'open')"
                    :options="collect($statusOptions)->map(fn ($s) => ['value' => $s->value, 'label' => $statusLabels[$s->value]])"
                />
            </div>

            <div class="min-w-0">
                <x-md3-select
                    name="priority"
                    label="Priority"
                    :value="old('priority', $issue?->priority->value ?? 'normal')"
                    :options="collect($priorityOptions)->map(fn ($p) => ['value' => $p->value, 'label' => $priorityLabels[$p->value]])"
                />
            </div>

            <div class="min-w-0" x-show="tracker === 'bug'" style="{{ $currentType === 'bug' ? '' : 'display:none' }}">
                <x-md3-select
                    name="severity"
                    label="Mức độ nghiêm trọng (Severity)"
                    placeholder="— Chọn —"
                    :value="old('severity', $issue?->severity?->value)"
                    :options="collect($severityOptions)->map(fn ($s) => ['value' => $s->value, 'label' => $severityLabels[$s->value]])"
                />
                <p class="mt-1 text-xs text-on-surface-variant">Chỉ áp dụng cho Tracker = Bug.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-md3-text-field name="start_date" label="Ngày bắt đầu" type="date" :value="old('start_date', $issue?->start_date?->format('Y-m-d'))" />
                <x-md3-text-field name="due_date" label="Hạn hoàn thành" type="date" :value="old('due_date', $issue?->due_date?->format('Y-m-d'))" />
            </div>

            <div class="min-w-0">
                <label class="text-xs font-medium text-on-surface-variant">% Hoàn thành</label>
                <div class="mt-2 flex items-center gap-3">
                    <input
                        type="range"
                        name="percent_done"
                        min="0"
                        max="100"
                        step="10"
                        x-model.number="percentDone"
                        class="flex-1 accent-primary"
                    >
                    <span class="min-w-12 rounded-full bg-primary-container px-2 py-0.5 text-center text-xs font-semibold text-on-primary-container" x-text="percentDone + '%'"></span>
                </div>
                <x-input-error :messages="$errors->get('percent_done')" class="mt-1" />
            </div>

            <div class="min-w-0">
                <x-md3-text-field name="estimated_hours" label="Ước lượng thời gian (giờ)" type="number" step="0.5" min="0" :value="old('estimated_hours', $issue?->estimated_hours)" />
            </div>

            <div class="min-w-0">
                <x-md3-text-field name="category" label="Danh mục" :value="old('category', $issue?->category)" />
            </div>
            <div class="min-w-0">
                <x-md3-select
                    name="assignee_id"
                    label="Người thực hiện"
                    placeholder="Chưa gán"
                    :value="old('assignee_id', $issue?->assignee_id)"
                    :options="$members->map(fn ($m) => ['value' => $m['id'], 'label' => $m['name']])"
                />
                <p class="mt-1 text-xs text-on-surface-variant">Chỉ liệt kê thành viên đã tham gia dự án.</p>
            </div>

            @if ($parentOptions->isNotEmpty())
                <div class="min-w-0">
                    <x-md3-select
                        name="parent_id"
                        label="Task cha"
                        placeholder="Không có"
                        :value="old('parent_id', $issue?->parent_id)"
                        :options="$parentOptions->map(fn ($option) => ['value' => $option->id, 'label' => '#'.$option->id.' '.$option->title])"
                    />
                </div>
            @endif

            <label class="flex items-center gap-2 text-sm text-on-surface">
                <input type="checkbox" name="is_private" value="1" @checked(old('is_private', $issue?->is_private)) class="accent-primary">
                Task riêng tư
            </label>
        </section>

        <section class="py-5">
            <h3 class="mb-3 font-medium text-on-surface">Tệp đính kèm</h3>

            @if ($issue && $issue->attachments->isNotEmpty())
                <ul class="mb-3 flex flex-col divide-y divide-outline-variant">
                    @foreach ($issue->attachments as $attachment)
                        <li class="py-2 text-sm text-on-surface">{{ $attachment->original_name }}</li>
                    @endforeach
                </ul>
            @endif

            <input
                type="file"
                name="attachments[]"
                multiple
                class="block w-full text-sm text-on-surface-variant file:mr-4 file:rounded-full file:border-0 file:bg-primary-container file:px-4 file:py-2 file:text-sm file:font-medium file:text-on-primary-container"
            >
            <x-input-error :messages="$errors->get('attachments.*')" class="mt-2" />
            <p class="mt-2 text-xs text-on-surface-variant">
                Tối đa 10MB mỗi tệp.{{ $issue ? ' Tệp mới sẽ được thêm vào danh sách hiện có.' : '' }}
            </p>
        </section>

        <section class="py-5">
            <h3 class="mb-3 font-medium text-on-surface">Người theo dõi</h3>
            @if ($members->isEmpty())
                <p class="text-sm text-on-surface-variant">Dự án chưa có thành viên nào khác.</p>
            @else
                <div class="flex flex-wrap gap-3">
                    @foreach ($members as $member)
                        <label class="flex items-center gap-2 rounded-full border border-outline-variant px-3 py-1.5 text-sm text-on-surface has-[:checked]:border-primary has-[:checked]:bg-primary-container has-[:checked]:text-on-primary-container">
                            <input
                                type="checkbox"
                                name="watchers[]"
                                value="{{ $member['id'] }}"
                                @checked($currentWatchers->contains($member['id']))
                                class="accent-primary"
                            >
                            {{ $member['name'] }}
                        </label>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ $cancelUrl }}">
            <x-secondary-button type="button">Hủy</x-secondary-button>
        </a>
        <x-primary-button>{{ $issue ? 'Lưu thay đổi' : 'Tạo Task' }}</x-primary-button>
    </div>
</form>
