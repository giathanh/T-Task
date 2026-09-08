<x-admin-layout title="Quản lý dự án">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('admin.projects.index') }}" class="flex flex-wrap items-center gap-3">
            <x-md3-text-field name="search" label="Tìm kiếm dự án" :value="$search" class="sm:w-80" />
            <x-secondary-button type="submit">Tìm kiếm</x-secondary-button>
            @if ($search !== '')
                <a href="{{ route('admin.projects.index') }}" class="text-sm text-primary hover:underline">Xóa tìm kiếm</a>
            @endif
        </form>
        <a href="{{ route('admin.projects.create') }}" class="inline-flex shrink-0 items-center justify-center glass-button glass-button--primary rounded-full px-6 py-3 text-sm font-medium text-on-primary">Thêm dự án</a>
    </div>
    <div class="overflow-x-auto rounded-2xl glass">
        <table class="w-full text-left text-sm text-on-surface">
            <caption class="sr-only">Danh sách dự án</caption>
            <thead class="bg-surface-container text-on-surface-variant"><tr><th scope="col" class="px-5 py-4">Tên dự án</th><th scope="col" class="px-5 py-4">Trạng thái</th><th scope="col" class="px-5 py-4">Hạn hoàn thành</th><th scope="col" class="px-5 py-4">Thành viên</th><th scope="col" class="px-5 py-4">Thao tác</th></tr></thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse ($projects as $item)
                    <tr><td class="px-5 py-4 font-medium">{{ $item->name }}</td><td class="px-5 py-4">{{ $item->status->label() }}</td><td class="px-5 py-4">{{ $item->due_date?->format('d/m/Y') ?? '—' }}</td><td class="px-5 py-4">{{ $item->members_count }}</td><td class="px-5 py-4"><a href="{{ route('admin.projects.edit', $item) }}" aria-label="Chỉnh sửa {{ $item->name }}" class="font-medium text-primary hover:underline">Chỉnh sửa</a></td></tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-on-surface-variant">{{ $search !== '' ? 'Không tìm thấy kết quả phù hợp.' : 'Chưa có dự án nào.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $projects->links() }}
</x-admin-layout>
