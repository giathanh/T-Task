<?php

namespace App\Policies;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use App\Models\WikiPage;

class WikiPagePolicy
{
    /**
     * Determine whether the user can list the project's wiki pages.
     */
    public function viewAny(User $user, Project $project): bool
    {
        return $this->isMember($user, $project);
    }

    /**
     * Determine whether the user can view the wiki page.
     */
    public function view(User $user, WikiPage $wikiPage): bool
    {
        return $this->isMember($user, $wikiPage->project);
    }

    /**
     * Determine whether the user can create a wiki page in the given project.
     */
    public function create(User $user, Project $project): bool
    {
        return $this->isMember($user, $project);
    }

    /**
     * Determine whether the user can update the wiki page.
     */
    public function update(User $user, WikiPage $wikiPage): bool
    {
        return $this->isMember($user, $wikiPage->project);
    }

    /**
     * Determine whether the user can delete the wiki page.
     */
    public function delete(User $user, WikiPage $wikiPage): bool
    {
        return $wikiPage->project->members()
            ->whereKey($user->id)
            ->wherePivotIn('role', [ProjectRole::Admin->value, ProjectRole::Leader->value])
            ->exists();
    }

    private function isMember(User $user, Project $project): bool
    {
        return $project->members()->whereKey($user->id)->exists();
    }
}
