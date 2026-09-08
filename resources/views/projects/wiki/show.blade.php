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
        <div class="flex w-full flex-col gap-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <a
                    href="{{ route('projects.wiki.index', $project) }}"
                    class="text-sm font-medium text-on-surface-variant transition hover:text-on-surface"
                >
                    ← Tất cả trang
                </a>

                <div class="flex items-center gap-2">
                    @can('update', $page)
                        <a
                            href="{{ route('projects.wiki.edit', [$project, $page]) }}"
                            class="rounded-full border border-outline px-4 py-1.5 text-sm font-medium text-primary transition hover:bg-primary/8"
                        >
                            Sửa
                        </a>
                    @endcan

                    @can('delete', $page)
                        <form
                            method="POST"
                            action="{{ route('projects.wiki.destroy', [$project, $page]) }}"
                            onsubmit="return confirm('Xoá trang wiki này? Hành động không thể hoàn tác.');"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="rounded-full border border-error px-4 py-1.5 text-sm font-medium text-error transition hover:bg-error/8"
                            >
                                Xoá
                            </button>
                        </form>
                    @endcan
                </div>
            </div>

            <article class="flex flex-col gap-4 rounded-3xl glass p-8">
                <header class="flex flex-col gap-1 border-b border-outline-variant pb-4">
                    <h1 class="text-2xl font-semibold text-on-surface">{{ $page->title }}</h1>
                    <p class="text-xs text-on-surface-variant">
                        Cập nhật {{ $page->updated_at->diffForHumans() }}
                        @if ($page->editor)
                            bởi {{ $page->editor->name }}
                        @endif
                        @if ($page->creator)
                            · Tạo bởi {{ $page->creator->name }}
                        @endif
                    </p>
                </header>

                @if ($contentHtml !== '')
                    <div class="wiki-content">{!! $contentHtml !!}</div>
                @else
                    <p class="text-on-surface-variant">Trang này chưa có nội dung.</p>
                @endif
            </article>
        </div>
    </div>
</x-app-layout>
