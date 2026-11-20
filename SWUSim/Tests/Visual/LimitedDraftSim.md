# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It describes a SIMULATION run, not a gamestate check.
#
# LIMITED DRAFT SIMULATION — whole drafts driven through the real pages by claudebot accounts
#
#   The owner's acceptance runs for Limited Plan 3 (docs/superpowers/plans/2026-11-05-swusim-limited-plan3-draft.md):
#   a 3-player draft and an 8-player draft with one seat going idle. Nothing is seeded: the room is created from the
#   menu, the seats join by the invite link, every pick is a click + Pick on the pick screen, every deck is built on the
#   builder. The one shortcut is Start, POSTed by the harness with testPickSeconds (local dev only) so an idle seat's
#   timeouts take seconds, not 5 minutes a pick.

## HOW TO RUN (local docker stack, http://localhost:3400)

    cd DevTools/ui-harness
    node swusim-limited-draft-sim.mjs --players 3
    node swusim-limited-draft-sim.mjs --players 8 --packs 3 --idle 5 --pick-seconds 15
    # Plan 4 — the whole event, Swiss rounds included:
    node swusim-limited-draft-sim.mjs --players 3 --swiss --pick-seconds 15
    node swusim-limited-draft-sim.mjs --players 8 --swiss --idle 5 --pick-seconds 15

Accounts: claudebot1…claudebot8 (password `pass`; claudebot5+ exist on the LOCAL database only). Chromium by default
(`--engine firefox|webkit`). Seat 1 runs at phone width (390x844), the rest at 1440x900.

## WHAT EACH RUN PROVES

- The whole loop works for real people: menu → room → invite joins → packs → start → leader draft → card rounds with
  passing and queues → the builder → Ready → Swiss round 1 starts by itself (Plan 4).
- Every live seat ends with N leaders + 14·N cards (3 packs: 3 + 42 = 45).
- `--idle K`: seat K stops after its first card pick of round 1; its packs time out and are auto-picked; the other seats
  see the kick prompt ON THE PICK SCREEN and vote; K is dropped and keeps auto-picking, so every pack still empties and
  the other seats' pools stay complete. K's own pick screen says "You were removed from this draft" and never opens the
  builder. (A true majority: with 8 seats, 4 of the 7 others; every other seat votes here.)
- The poll stays under 60 KB and the draft state under 120 KB; no console errors on any page.
- `--swiss` (Plan 4): the room sends each seat with a table to its board, between rounds every seat Readies and the host
  presses Start round, and the event ends at the final standings: 3 rounds, nobody meets twice, ⌊N/2⌋ matches a round
  plus a bye when N is odd (the 8-seat run with an idle seat plays 7: a bye every round), every live seat in every round,
  one standings row per seat, rank 1 = the winner. It prints each round's results and the top 3.

## WHAT IT DOES NOT PROVE

- Without `--swiss`: anything about the games.
- With `--swiss`, only that the rounds run end to end: every game is ended by a concede (match seat 2 concedes in odd
  rounds, seat 1 in even ones), so no real play, no Bo3 between-games builder, and no in-game kick.
- Real 5-minute clocks (shortened for the run), real touch, or Safari on a phone.
