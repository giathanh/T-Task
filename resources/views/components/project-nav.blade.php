@props([
    'project',
    'active' => null,
])

@php
    $projectId = is_array($project) ? $project['id'] : $project->id;

    $tabs = [
        ['key' => 'overview', 'label' => 'Tổng quan', 'url' => route('projects.show', $projectId)],
        ['key' => 'activity', 'label' => 'Activity', 'url' => route('projects.activity', $projectId)],
        ['key' => 'issues', 'label' => 'Issues', 'url' => route('issues.index', $projectId)],
        ['key' => 'wiki', 'label' => 'Wiki', 'url' => route('projects.wiki.index', $projectId)],
        ['key' => 'calendar', 'label' => 'Calendar', 'url' => route('projects.calendar', $projectId)],
    ];

    $baseClasses = '-mb-px inline-flex shrink-0 items-center border-b-2 px-3 py-2 text-sm font-medium transition-colors';
    $activeClasses = 'border-primary text-primary';
    $linkClasses = 'border-transparent text-on-surface-variant hover:border-outline hover:bg-on-surface/5 hover:text-on-surface';
    $disabledClasses = 'cursor-not-allowed border-transparent text-on-surface-variant/40';
@endphp

<nav
    aria-label="{{ __('Project navigation') }}"
    class="-mx-4 -mb-6 flex overflow-x-auto border-b border-outline-variant px-4 sm:mx-0 sm:px-0"
>
    @foreach ($tabs as $tab)
        @php $isActive = $active === $tab['key']; @endphp

        @if ($isActive)
            <a href="{{ $tab['url'] }}" aria-current="page" class="{{ $baseClasses }} {{ $activeClasses }}">
                {{ $tab['label'] }}
            </a>
        @elseif ($tab['url'])
            <a href="{{ $tab['url'] }}" class="{{ $baseClasses }} {{ $linkClasses }}">
                {{ $tab['label'] }}
            </a>
        @else
            <span
                class="{{ $baseClasses }} {{ $disabledClasses }}"
                title="{{ __('Sắp có') }}"
                aria-disabled="true"
            >
                {{ $tab['label'] }}
            </span>
        @endif
    @endforeach
</nav>
