<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4">
            <h2 class="text-xl font-medium leading-tight text-on-surface">
                {{ $project['name'] }}
            </h2>

            <x-project-nav :project="$project" active="overview" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="w-full px-4 sm:px-6 lg:px-8">
            <div id="project-show"></div>
            <script>
                window.__INITIAL_PROPS__ = window.__INITIAL_PROPS__ || {};
                window.__INITIAL_PROPS__['project-show'] = {{ Js::from([
                    'project' => $project,
                    'issueStats' => $issueStats,
                    'members' => $members,
                    'currentUserRole' => $currentUserRole,
                    'newIssueUrl' => $newIssueUrl,
                ]) }};
            </script>
        </div>
    </div>
</x-app-layout>
