<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4">
            <h2 class="text-xl font-medium leading-tight text-on-surface">
                Thêm Task — {{ $project['name'] }}
            </h2>

            <x-project-nav :project="$project" active="issues" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            @include('issues.partials.form', [
                'project' => $project,
                'action' => route('issues.store', $project['id']),
                'members' => $members,
                'parentOptions' => $parentOptions,
                'trackerOptions' => $trackerOptions,
                'statusOptions' => $statusOptions,
                'priorityOptions' => $priorityOptions,
                'severityOptions' => $severityOptions,
            ])
        </div>
    </div>
</x-app-layout>
