<x-admin-layout :title="$project->exists ? 'Chỉnh sửa dự án' : 'Thêm dự án'">
    <form method="POST" action="{{ $project->exists ? route('admin.projects.update', $project) : route('admin.projects.store') }}" class="flex w-full max-w-3xl flex-col gap-6 rounded-2xl glass p-6">
        @csrf
        @if ($project->exists)
            @method('PUT')
        @endif
        <x-md3-text-field name="name" label="Tên dự án" :value="$project->name" required maxlength="255" />
        <x-md3-textarea name="description" label="Mô tả" :value="$project->description" rows="5" />
        <x-md3-select name="status" label="Trạng thái" :options="collect(\App\Enums\ProjectStatus::cases())->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()])->all()" :value="$project->status?->value ?? 'planning'" required />
        <x-md3-text-field name="due_date" label="Hạn hoàn thành" type="date" :value="$project->due_date?->format('Y-m-d')" />
        <div class="flex items-center gap-4">
            <x-primary-button>Lưu dự án</x-primary-button>
            <a href="{{ route('admin.projects.index') }}" class="text-sm font-medium text-primary hover:underline">Hủy</a>
        </div>
    </form>
</x-admin-layout>
