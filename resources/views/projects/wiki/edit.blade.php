<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4">
            <h2 class="text-xl font-medium leading-tight text-on-surface">
                Sửa: {{ $page->title }}
            </h2>

            <x-project-nav :project="$project" active="wiki" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            @include('projects.wiki.partials.form', [
                'project' => $project,
                'page' => $page,
                'action' => route('projects.wiki.update', [$project, $page]),
                'method' => 'PUT',
            ])
        </div>
    </div>
</x-app-layout>
