<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIssueNoteRequest;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;

class IssueNoteController extends Controller
{
    public function store(StoreIssueNoteRequest $request, Project $project, Issue $issue): RedirectResponse
    {
        $issue->notes()->create([
            'body' => $request->validated('body'),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->to(route('issues.show', [$project, $issue]).'#notes')
            ->with('status', 'issue-note-created');
    }
}
