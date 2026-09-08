<x-admin-layout title="Quản lý người dùng">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-center gap-3">
            <x-md3-text-field name="search" label="Tìm kiếm người dùng" :value="$search" class="sm:w-80" />
            <x-secondary-button type="submit">Tìm kiếm</x-secondary-button>
            @if ($search !== '')
                <a href="{{ route('admin.users.index') }}" class="text-sm text-primary hover:underline">Xóa tìm kiếm</a>
            @endif
        </form>
        <a href="{{ route('admin.users.create') }}" class="inline-flex shrink-0 items-center justify-center rounded-full bg-primary px-6 py-3 text-sm font-medium text-on-primary hover:bg-primary/90">Thêm người dùng</a>
    </div>
    <div class="overflow-x-auto rounded-2xl border border-outline-variant bg-surface-container-lowest">
        <table class="w-full text-left text-sm text-on-surface">
            <caption class="sr-only">Danh sách người dùng</caption>
            <thead class="bg-surface-container text-on-surface-variant"><tr><th scope="col" class="px-5 py-4">Họ tên</th><th scope="col" class="px-5 py-4">Email</th><th scope="col" class="px-5 py-4">Quyền hệ thống</th><th scope="col" class="px-5 py-4">Dự án</th><th scope="col" class="px-5 py-4">Thao tác</th></tr></thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse ($users as $item)
                    <tr><td class="px-5 py-4 font-medium">{{ $item->name }}</td><td class="px-5 py-4">{{ $item->email }}</td><td class="px-5 py-4"><span class="rounded-full bg-secondary-container px-3 py-1 text-sm text-on-secondary-container">{{ $item->is_admin ? 'Quản trị viên' : 'Người dùng' }}</span></td><td class="px-5 py-4">{{ $item->projects_count }}</td><td class="px-5 py-4"><a href="{{ route('admin.users.edit', $item) }}" aria-label="Chỉnh sửa {{ $item->name }}" class="font-medium text-primary hover:underline">Chỉnh sửa</a></td></tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-on-surface-variant">{{ $search !== '' ? 'Không tìm thấy kết quả phù hợp.' : 'Chưa có người dùng nào.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
</x-admin-layout>
