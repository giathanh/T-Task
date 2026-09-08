<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4">
            <h2 class="text-xl font-medium leading-tight text-on-surface">
                Trang wiki mới — {{ $project->name }}
            </h2>

            <x-project-nav :project="$project" active="wiki" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            @include('projects.wiki.partials.form', [
                'project' => $project,
                'action' => route('projects.wiki.store', $project),
            ])
        </div>
    </div>
</x-app-layout>
