<?php

namespace App\Policies;

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;

class IssuePolicy
{
    /**
     * Determine whether the user can list the given project's issues.
     */
    public function viewAny(User $user, Project $project): bool
    {
        return $project->members()->whereKey($user->id)->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Issue $issue): bool
    {
        return $issue->project->members()->whereKey($user->id)->exists();
    }

    /**
     * Determine whether the user can create issues within the given project.
     */
    public function create(User $user, Project $project): bool
    {
        return $project->members()->whereKey($user->id)->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Issue $issue): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Issue $issue): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Issue $issue): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Issue $issue): bool
    {
        return false;
    }
}
