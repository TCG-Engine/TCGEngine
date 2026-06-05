---
name: swusim-session-close
description: Use when the user says "let's close this SWU Sim session" or otherwise signals they are done with a SWUSim coding session and want to wrap up.
---

# SWUSim Session Close

Update the project memory file before ending the session so the next session starts with accurate context.

## Memory File

```
/Users/mariotorresjr/.claude/projects/-Users-mariotorresjr-Documents-GitHub-Karabast-SWU-SWUStats/memory/swusim-project.md
```

## Steps

### 1. Gather session diff

Run `git diff HEAD --stat` and `git log --oneline -10` to see what changed.

### 2. Update the memory file — touch only what changed

**Header line** — bump the date/session label:
```
# SWUSim — Project Summary (as of YYYY-MM-DD, updated YYYY-MM-DD session N)
```

**`## File Status → Completed`** — for every file meaningfully changed or finished this session, write or update its entry. Include:
- What the file does (1–3 sentences)
- Key decisions baked into it (not obvious from the file name)
- Any gotchas future-you must know (guard conditions, naming conventions, engine assumptions)

**`## File Status → Not Yet Started`** — re-rank the priority list based on what's done. Remove items that are now complete. Add new items if the session surfaced new required work. Keep the numbered priority order.

**`## Architecture Decisions`** — add any new decisions made this session. Do NOT remove existing entries unless they were explicitly reversed.

**`## GameLayout.php` entry in Completed** — if the board layout changed (it often does), rewrite the layout description to match the current actual layout. Be specific: what's in each column/region, what the resource badge does, how the sidebar works.

### 3. Write the update

Use the Write tool to save the full updated file. Do not leave stale entries — if something from "Not Yet Started" is now partially done, note its partial status.

### 4. Confirm

Tell the user:
- What sections were updated
- What the current top priority task is for the next session
- Whether there are uncommitted changes they should commit before closing

## What NOT to do

- Do not add entries for files that were only read (not changed)
- Do not remove architecture decisions unless explicitly reversed this session  
- Do not summarize the whole project — only update the delta
- Do not change the CardID Format, Reference Files, or Deck Builders sections unless those actually changed
