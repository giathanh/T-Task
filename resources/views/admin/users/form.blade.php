<x-admin-layout :title="$user->exists ? 'Chỉnh sửa người dùng' : 'Thêm người dùng'">
    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="flex w-full max-w-3xl flex-col gap-6 rounded-2xl border border-outline-variant bg-surface-container-lowest p-6">
        @csrf
        @if ($user->exists)
            @method('PUT')
        @endif
        <x-md3-text-field name="name" label="Họ tên" :value="$user->name" required maxlength="255" autocomplete="name" />
        <x-md3-text-field name="email" label="Email" type="email" :value="$user->email" required maxlength="255" autocomplete="email" />
        <div>
            <label for="password" class="mb-2 block text-sm text-on-surface">Mật khẩu{{ $user->exists ? ' mới (để trống để giữ nguyên)' : '' }}</label>
            <x-text-input id="password" name="password" type="password" class="w-full" autocomplete="new-password" :required="! $user->exists" />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>
        <div>
            <label for="password_confirmation" class="mb-2 block text-sm text-on-surface">Xác nhận mật khẩu</label>
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="w-full" autocomplete="new-password" :required="! $user->exists" />
        </div>
        <x-md3-select name="is_admin" label="Quyền hệ thống" :options="[['value' => '0', 'label' => 'Người dùng'], ['value' => '1', 'label' => 'Quản trị viên']]" :value="$user->is_admin ? '1' : '0'" required />
        @if ($user->is(auth()->user()))
            <p class="text-sm text-on-surface-variant">Bạn không thể tự gỡ quyền quản trị của mình.</p>
        @endif
        <div class="flex items-center gap-4">
            <x-primary-button>Lưu người dùng</x-primary-button>
            <a href="{{ route('admin.users.index') }}" class="text-sm font-medium text-primary hover:underline">Hủy</a>
        </div>
    </form>
</x-admin-layout>
