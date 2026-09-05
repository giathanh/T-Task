<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4">
            <h2 class="text-xl font-medium leading-tight text-on-surface">
                Sửa issue #{{ $issue->id }} — {{ $project['name'] }}
            </h2>

            <x-project-nav :project="$project" active="issues" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            @include('issues.partials.form', [
                'project' => $project,
                'issue' => $issue,
                'action' => route('issues.update', [$project['id'], $issue->id]),
                'method' => 'PUT',
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
