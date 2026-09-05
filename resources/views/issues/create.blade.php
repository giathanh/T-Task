@php
    $statusLabels = ['open' => 'Chưa làm', 'in_progress' => 'Đang làm', 'done' => 'Hoàn thành'];
    $priorityLabels = ['low' => 'Thấp', 'normal' => 'Bình thường', 'high' => 'Cao', 'urgent' => 'Khẩn cấp'];
    $severityLabels = ['low' => 'Thấp', 'medium' => 'Trung bình', 'high' => 'Cao', 'critical' => 'Nghiêm trọng'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-medium leading-tight text-on-surface">
            Thêm Task — {{ $project['name'] }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            <form
                method="POST"
                action="{{ route('issues.store', $project['id']) }}"
                enctype="multipart/form-data"
                x-data="{ tracker: '{{ old('type', 'task') }}', percentDone: {{ (int) old('percent_done', 0) }} }"
                class="flex flex-col gap-6"
            >
                @csrf

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    {{-- Main column --}}
                    <div class="flex flex-col gap-6 lg:col-span-2">
                        <section class="rounded-3xl bg-surface-container-lowest p-6 shadow-elevation-1">
                            <x-md3-text-field name="title" label="Tiêu đề" required autofocus />

                            <div class="mt-4">
                                <x-md3-textarea name="description" label="Mô tả" :rows="6" />
                            </div>
                        </section>

                        <section class="rounded-3xl bg-surface-container-lowest p-6 shadow-elevation-1">
                            <h3 class="mb-3 font-medium text-on-surface">Tệp đính kèm</h3>
                            <input
                                type="file"
                                name="attachments[]"
                                multiple
                                class="block w-full text-sm text-on-surface-variant file:mr-4 file:rounded-full file:border-0 file:bg-primary-container file:px-4 file:py-2 file:text-sm file:font-medium file:text-on-primary-container"
                            >
                            <x-input-error :messages="$errors->get('attachments.*')" class="mt-2" />
                            <p class="mt-2 text-xs text-on-surface-variant">Tối đa 10MB mỗi tệp.</p>
                        </section>

                        <section class="rounded-3xl bg-surface-container-lowest p-6 shadow-elevation-1">
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
                                                @checked(collect(old('watchers', []))->contains($member['id']))
                                                class="accent-primary"
                                            >
                                            {{ $member['name'] }}
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </section>
                    </div>

                    {{-- Sidebar column --}}
                    <div class="flex flex-col gap-4">
                        <section class="rounded-3xl bg-surface-container-lowest p-6 shadow-elevation-1">
                            <div class="grid grid-cols-2 gap-4">
                                <x-md3-select
                                    name="type"
                                    label="Tracker"
                                    x-model="tracker"
                                    :value="old('type', 'task')"
                                    :options="collect($trackerOptions)->map(fn ($t) => ['value' => $t->value, 'label' => ucfirst($t->value)])"
                                />

                                <x-md3-select
                                    name="status"
                                    label="Status"
                                    :value="old('status', 'open')"
                                    :options="collect($statusOptions)->map(fn ($s) => ['value' => $s->value, 'label' => $statusLabels[$s->value]])"
                                />
                            </div>

                            <div class="mt-4">
                                <x-md3-select
                                    name="priority"
                                    label="Priority"
                                    :value="old('priority', 'normal')"
                                    :options="collect($priorityOptions)->map(fn ($p) => ['value' => $p->value, 'label' => $priorityLabels[$p->value]])"
                                />
                            </div>

                            <div class="mt-4" x-show="tracker === 'bug'" style="{{ old('type', 'task') === 'bug' ? '' : 'display:none' }}">
                                <x-md3-select
                                    name="severity"
                                    label="Mức độ nghiêm trọng (Severity)"
                                    placeholder="— Chọn —"
                                    :value="old('severity')"
                                    :options="collect($severityOptions)->map(fn ($s) => ['value' => $s->value, 'label' => $severityLabels[$s->value]])"
                                />
                                <p class="mt-1 text-xs text-on-surface-variant">Chỉ áp dụng cho Tracker = Bug.</p>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-4">
                                <x-md3-text-field name="start_date" label="Ngày bắt đầu" type="date" />
                                <x-md3-text-field name="due_date" label="Hạn hoàn thành" type="date" />
                            </div>

                            <div class="mt-4">
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

                            <div class="mt-4">
                                <x-md3-text-field name="estimated_hours" label="Ước lượng thời gian (giờ)" type="number" step="0.5" min="0" />
                            </div>

                            <div class="mt-4">
                                <x-md3-text-field name="category" label="Danh mục" />
                            </div>
                        </section>

                        <section class="rounded-3xl bg-surface-container-lowest p-6 shadow-elevation-1">
                            <x-md3-select
                                name="assignee_id"
                                label="Người thực hiện"
                                placeholder="Chưa gán"
                                :value="old('assignee_id')"
                                :options="$members->map(fn ($m) => ['value' => $m['id'], 'label' => $m['name']])"
                            />
                            <p class="mt-1 text-xs text-on-surface-variant">Chỉ liệt kê thành viên đã tham gia dự án.</p>

                            @if ($parentOptions->isNotEmpty())
                                <div class="mt-4">
                                    <x-md3-select
                                        name="parent_id"
                                        label="Task cha"
                                        placeholder="Không có"
                                        :value="old('parent_id')"
                                        :options="$parentOptions->map(fn ($issue) => ['value' => $issue->id, 'label' => '#'.$issue->id.' '.$issue->title])"
                                    />
                                </div>
                            @endif

                            <label class="mt-4 flex items-center gap-2 text-sm text-on-surface">
                                <input type="checkbox" name="is_private" value="1" @checked(old('is_private')) class="accent-primary">
                                Task riêng tư
                            </label>
                        </section>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('projects.show', $project['id']) }}">
                        <x-secondary-button type="button">Hủy</x-secondary-button>
                    </a>
                    <x-primary-button>Tạo Task</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
