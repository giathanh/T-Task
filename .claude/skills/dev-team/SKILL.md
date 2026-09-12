---
name: dev-team
description: "Run a task through a three-agent dev team using Claude Code Agent Teams: an Opus leader plans and verifies, a Sonnet developer implements, an Opus reviewer reviews, then the leader reports back. Use for feature work or bug fixes the user wants planned, built, and independently reviewed. Invoke with /dev-team <task description>."
disable-model-invocation: true
argument-hint: <task description>
---

# Dev Team

You are the **team-lead** session. You do not plan, code, or review. You spawn the team, hand the task to the leader, relay questions and the final report to the user, and shut the team down.

Requires Agent Teams (`CLAUDE_CODE_EXPERIMENTAL_AGENT_TEAMS=1`, set in `.claude/settings.json`). If teammate spawning is unavailable in this session, stop and tell the user to restart Claude Code so the setting is picked up.

## Task from the user

$ARGUMENTS

If `$ARGUMENTS` is empty, ask the user what the team should build and stop.

## Step 1 — Spawn the team

Spawn three teammates at once, all in the background, using the project agent definitions. Names and definitions are fixed:

| Teammate name | Agent definition     | Model  | Role                          |
| ------------- | -------------------- | ------ | ----------------------------- |
| `leader`      | `dev-team-leader`    | opus   | plans, verifies, reports      |
| `developer`   | `dev-team-developer` | sonnet | implements the plan           |
| `reviewer`    | `dev-team-reviewer`  | opus   | reviews, runs fix rounds      |

Each spawn prompt must be self-contained. Include, verbatim, the task text above and this roster (who is who, and that the spawning session is `team-lead`). Tell `developer` and `reviewer` to wait for their trigger message (`developer` waits for the plan from `leader`; `reviewer` waits for `READY FOR REVIEW` from `developer`). Tell `leader` to start planning immediately.

## Step 2 — Hand off and wait

Once all three are up, send `leader` the task with `SendMessage` (even though it is in the spawn prompt — this is the explicit go signal). Then wait. Do not read the code, do not edit files, do not run tests. The flow inside the team is:

```
1. leader    --plan-------------------> developer
2. developer --READY FOR REVIEW-------> reviewer
3. reviewer  --CHANGES REQUESTED------> developer   (repeat 2-3, max 3 rounds)
4. reviewer  --APPROVED---------------> leader
5. leader    verifies diff + tests; may loop to developer (max 2 times)
6. leader    --FINAL REPORT-----------> team-lead (you) --> user
```

While waiting, handle only these events:

- **A teammate asks a question meant for the user** — forward it to the user verbatim (say which teammate asked), wait for the answer, and send it back to that teammate with `SendMessage`.
- **A teammate goes idle without reporting** — check `TaskList`. If work remains, send a short nudge to the teammate that owns the next step.
- **`BLOCKED` from `reviewer` or `leader`** — do not try to resolve it yourself. Tell the user what is blocked and ask how to proceed; relay their decision to `leader`.

Do not summarize partial progress to the user unless they ask.

## Step 3 — Report and shut down

When `leader` sends a message starting with `FINAL REPORT`:

1. Show the report to the user in full, unchanged, in the language they used.
2. Add one line noting that changes are uncommitted in the working tree and offer to commit or open the diff.
3. Send a shutdown request to `developer`, `reviewer`, and `leader` (in that order) and confirm they are gone with `ListAgents`.

Never commit on the team's behalf unless the user asks after seeing the report.
