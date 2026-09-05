<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-medium leading-tight text-on-surface">
            {{ $project['name'] }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div id="project-show"></div>
            <script>
                window.__INITIAL_PROPS__ = window.__INITIAL_PROPS__ || {};
                window.__INITIAL_PROPS__['project-show'] = {{ Js::from([
                    'project' => $project,
                    'issueStats' => $issueStats,
                    'members' => $members,
                    'currentUserRole' => $currentUserRole,
                ]) }};
            </script>
        </div>
    </div>
</x-app-layout>
