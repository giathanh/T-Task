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

    $baseClasses = 'inline-flex shrink-0 items-center rounded-full px-4 py-2 text-sm font-medium transition';
    $activeClasses = 'glass-button glass-button--secondary text-on-secondary-container';
    $linkClasses = 'text-on-surface-variant hover:bg-on-surface/8 hover:text-on-surface';
    $disabledClasses = 'cursor-not-allowed text-on-surface-variant/40';
@endphp

<nav
    aria-label="{{ __('Project navigation') }}"
    class="flex gap-1 overflow-x-auto rounded-full glass-strong p-1.5 sm:w-max sm:max-w-full"
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
