@props([
    'project',
    'active' => null,
])

@php
    $projectId = is_array($project) ? $project['id'] : $project->id;

    $tabs = [
        ['key' => 'overview', 'label' => 'Tổng quan', 'url' => route('projects.show', $projectId)],
        ['key' => 'activity', 'label' => 'Activity', 'url' => null],
        ['key' => 'issues', 'label' => 'Issues', 'url' => null],
        ['key' => 'wiki', 'label' => 'Wiki', 'url' => route('projects.wiki.index', $projectId)],
        ['key' => 'calendar', 'label' => 'Calendar', 'url' => null],
    ];

    $baseClasses = 'inline-flex shrink-0 items-center rounded-full px-4 py-2 text-sm font-medium transition';
    $activeClasses = 'bg-secondary-container text-on-secondary-container';
    $linkClasses = 'text-on-surface-variant hover:bg-on-surface/8 hover:text-on-surface';
    $disabledClasses = 'cursor-not-allowed text-on-surface-variant/40';
@endphp

<nav
    aria-label="{{ __('Project navigation') }}"
    class="-mx-4 flex gap-1 overflow-x-auto px-4 py-1 sm:mx-0 sm:px-0"
>
    @foreach ($tabs as $tab)
        @php $isActive = $active === $tab['key']; @endphp

        @if ($isActive)
            <span aria-current="page" class="{{ $baseClasses }} {{ $activeClasses }}">
                {{ $tab['label'] }}
            </span>
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
