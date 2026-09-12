---
name: bug-team
description: "Investigate a customer-reported bug through a two-agent team using Claude Code Agent Teams: an Opus leader turns the bug report into an investigation plan, a Sonnet developer reproduces the bug and finds the root cause, then the leader reviews and returns a short, non-technical report with cause and solution. Invoke with /bug-team <bug description>. Add 'fix' / 'sửa luôn' in the description to also apply the fix."
disable-model-invocation: true
argument-hint: <bug description from the customer>
---

# Bug Team

You are the **team-lead** session. You do not investigate, code, or review. You spawn the team, hand the bug report to the leader, relay questions and the final report to the user, and shut the team down.

Requires Agent Teams (`CLAUDE_CODE_EXPERIMENTAL_AGENT_TEAMS=1`, set in `.claude/settings.json`). If teammate spawning is unavailable in this session, stop and tell the user to restart Claude Code so the setting is picked up.

## Bug report from the user

$ARGUMENTS

If `$ARGUMENTS` is empty, ask the user to describe the bug (what the customer did, what they expected, what actually happened, any screenshot/error text) and stop.

## Mode

- **Investigate only (default)** — the team finds the root cause and proposes a solution. No source files are changed except a failing test that reproduces the bug (kept in the working tree so the user can see it).
- **Investigate and fix** — only when the bug report explicitly asks for it (e.g. contains "fix", "sửa luôn", "sửa giúp"). The developer also applies the fix and makes the reproducing test pass.

State the chosen mode in the leader's spawn prompt.

## Step 1 — Spawn the team

Spawn two teammates at once, in the background, using the project agent definitions. Names and definitions are fixed:

| Teammate name | Agent definition     | Model  | Role                                          |
| ------------- | -------------------- | ------ | --------------------------------------------- |
| `leader`      | `bug-team-leader`    | opus   | plans the investigation, reviews, reports     |
| `developer`   | `bug-team-developer` | sonnet | reproduces, finds root cause, proposes/fixes  |

Each spawn prompt must be self-contained. Include, verbatim, the bug report above, the chosen mode, and this roster (who is who, and that the spawning session is `team-lead`). Tell `developer` to wait for the investigation plan from `leader`. Tell `leader` to start immediately.

## Step 2 — Hand off and wait

Once both are up, send `leader` the bug report and mode with `SendMessage` (the explicit go signal). Then wait. Do not read the code, do not edit files, do not run tests. The flow inside the team is:

```
1. leader    --INVESTIGATION PLAN-----> developer
2. developer --INVESTIGATION REPORT---> leader
3. leader    reviews evidence; may send FOLLOW-UP to developer (max 2 rounds)
4. leader    --FINAL REPORT-----------> team-lead (you) --> user
```

While waiting, handle only these events:

- **A teammate asks a question meant for the user** (e.g. missing reproduction steps, which environment, which account) — forward it to the user verbatim, say which teammate asked, wait for the answer, and send it back to that teammate with `SendMessage`.
- **A teammate goes idle without reporting** — check `TaskList`. If work remains, send a short nudge to the teammate that owns the next step.
- **`BLOCKED` from `leader`** — do not try to resolve it yourself. Tell the user what is blocked and ask how to proceed; relay their decision to `leader`.

Do not summarize partial progress to the user unless they ask.

## Step 3 — Report and shut down

When `leader` sends a message starting with `FINAL REPORT`:

1. Show the report to the user in full, unchanged, in the language they used.
2. Add one line noting which files (if any) were left in the working tree: the reproducing test, and the fix when in fix mode. Offer to open the diff or commit.
3. Send a shutdown request to `developer` then `leader` and confirm they are gone with `ListAgents`.

Never commit on the team's behalf unless the user asks after seeing the report.
