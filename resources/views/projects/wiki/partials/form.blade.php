@props([
    'project',
    'page' => null,
    'action',
    'method' => 'POST',
])

<form method="POST" action="{{ $action }}" class="flex flex-col gap-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <section class="flex flex-col gap-4 rounded-3xl bg-surface-container-lowest p-6 shadow-elevation-1">
        <x-md3-text-field
            name="title"
            label="Tiêu đề"
            :value="$page?->title"
            required
            autofocus
        />

        <div>
            <x-md3-textarea
                name="content"
                label="Nội dung"
                :value="$page?->content"
                :rows="18"
            />
            <p class="mt-1 px-4 text-xs text-on-surface-variant">
                Hỗ trợ Markdown: <code>**đậm**</code>, <code># Tiêu đề</code>, <code>- danh sách</code>,
                <code>`code`</code>, bảng, liên kết. HTML thô sẽ bị vô hiệu hoá.
            </p>
        </div>
    </section>

    <div class="flex justify-end gap-3">
        <a href="{{ $page
            ? route('projects.wiki.show', [$project, $page])
            : route('projects.wiki.index', $project) }}">
            <x-secondary-button type="button">Hủy</x-secondary-button>
        </a>
        <x-primary-button>{{ $page ? 'Lưu thay đổi' : 'Tạo trang' }}</x-primary-button>
    </div>
</form>
