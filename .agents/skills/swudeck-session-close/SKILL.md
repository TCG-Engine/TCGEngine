---
name: swudeck-session-close
description: Use at the end of a SWUDeck (NOT overall TCGEngine work) working session to run a short retro, capture durable lessons, and hand off cleanly. Invoke when the user says they're wrapping up, asks to "close the session", "run the retro", or asks "what were the lessons learned this session".
---

# SWUDeck Session Close

A lightweight end-of-session ritual: surface process lessons, route durable facts to the
right place, and leave the working tree in a clean, hand-off-ready state.

## When to run

- The user signals a session is wrapping up ("let's close out SWUDeck", "we're done with SWUDeck for today").
- The user explicitly asks to run the retro / for lessons learned for SWUDeck.
- A large multi-turn effort for SWUDeck just landed and it's worth capturing what was learned.

## Steps

Create a todo per step and work through them in order.

### 1. Retro — surface the lessons

Answer, honestly and specifically: **"What were some lessons learned from this session
that we might be able to use to improve future development?"**

Draw from what actually happened this session — the wrong turns as much as the wins. Good
lessons are concrete and reusable ("verify CSS layout in Firefox/WebKit, not just
Chromium — `height:100%` doesn't resolve through a flex-stretched parent in strict
engines"), not platitudes ("test more"). Prefer lessons that would have saved real time or
avoided a wrong conclusion.

### 2. Append to the retro log

Add a dated entry to `references/lessons-learned.md` (newest at the bottom). Format:

```markdown
## YYYY-MM-DD — <short session title>
- **<lesson headline>.** <1-2 sentences: what happened, what to do next time.>
- ...
```

Keep each lesson to a tight bullet. This log is for **process/development** lessons.

### 3. Route durable facts to memory

A retro lesson and a durable fact are different things. If the session surfaced a durable
**codebase gotcha, tool/credential fact, or project constraint** that will matter in future
sessions regardless of this retro, write it to the memory system (`memory/` + a pointer in
`MEMORY.md`) — that's what gets recalled next time. The retro log is not loaded as context;
memory is. When in doubt, do both: a terse process bullet here, the durable fact in memory.

Examples of things that belong in memory, not (only) here:
- A reusable codebase gotcha (a CSS/engine behavior, a generator quirk, a cache guard).
- A verification fact (test creds, which DB column holds what, how to reproduce a class of bug).
- A project constraint the user restated.

### 4. Hand-off hygiene

- Confirm the working tree state and remind the user of any uncommitted changes — **the
  user commits, never the assistant** (standing rule this project). List the files touched
  this session so they know what to review.
- Note any verification that's still pending or any known-but-unaddressed follow-ups, so
  nothing is silently dropped.
- Clean up throwaway artifacts created for verification (scratch test decks, temp accounts,
  debug files) or flag them for the user to remove.

## Output

A short retro summary to the user (the lessons), confirmation of what was written where
(retro log vs memory), and the hand-off notes (uncommitted files, pending items).
