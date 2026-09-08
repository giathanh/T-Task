<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4">
            <h2 class="text-xl font-medium leading-tight text-on-surface">
                Wiki — {{ $project->name }}
            </h2>

            <x-project-nav :project="$project" active="wiki" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto flex max-w-5xl flex-col gap-6 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <h3 class="font-medium text-on-surface">Các trang ({{ $pages->total() }})</h3>
                <a
                    href="{{ route('projects.wiki.create', $project) }}"
                    class="rounded-full bg-primary px-4 py-1.5 text-sm font-medium text-on-primary"
                >
                    + Trang mới
                </a>
            </div>

            @if ($pages->isEmpty())
                <section class="rounded-3xl bg-surface-container-lowest p-10 text-center shadow-elevation-1">
                    <p class="text-on-surface-variant">Dự án chưa có trang wiki nào.</p>
                    <a
                        href="{{ route('projects.wiki.create', $project) }}"
                        class="mt-4 inline-flex rounded-full bg-primary px-4 py-1.5 text-sm font-medium text-on-primary"
                    >
                        Tạo trang đầu tiên
                    </a>
                </section>
            @else
                <section class="overflow-hidden rounded-3xl bg-surface-container-lowest shadow-elevation-1">
                    <ul class="flex flex-col divide-y divide-outline-variant">
                        @foreach ($pages as $page)
                            <li>
                                <a
                                    href="{{ route('projects.wiki.show', [$project, $page]) }}"
                                    class="flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-on-surface/8"
                                >
                                    <span class="font-medium text-on-surface">{{ $page->title }}</span>
                                    <span class="shrink-0 text-xs text-on-surface-variant">
                                        Sửa lần cuối
                                        {{ $page->updated_at->diffForHumans() }}
                                        @if ($page->editor)
                                            · {{ $page->editor->name }}
                                        @endif
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{ $pages->links() }}
        </div>
    </div>
</x-app-layout>
