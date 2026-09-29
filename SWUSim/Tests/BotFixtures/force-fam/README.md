# Force Fam — community and content-creator decks

Decks contributed by named people rather than lifted from a tournament standing. The owner's own lists live
here too. Created 2026-09-29 with `krennic_ninin.txt` (swustats deck 108757, reference game 1431598).

## Why this is a separate directory

`meta-2026-09/` answers "how do our bots do against the field", so every deck in it is a tournament result
and its fixtures are named for their leader/set/base. This directory answers something different: it is
material for letting people **play against a creator's actual deck** in Petranaki Arena's Arenabot, with the
creator's permission. Attribution is part of the point, so the deck's *provenance* matters as much as its
contents — which the meta naming convention deliberately has no room for.

⚠ **These are NOT field fixtures.** Do not add them to a strength gate or a matchup sweep as if they were the
meta: a creator's list is chosen because someone plays it, not because it placed. Same rule as
`weak-2026-09/`.

## Planned: pickable categories (not built yet)

The Bot Arena deck picker will group fixtures by directory, with a display name per category:

| directory | category shown to players |
|---|---|
| `meta-2026-09` | SWU Competitive Hub Meta September 2026 |
| `force-fam` | Force Fam |

⚠ **Until that exists, nothing here is selectable in the Bot Arena.**
`SWUSim/DevTools/regen-deck-labels.php` globs `meta-2026-09/*.txt` ONLY, so these decks are absent from
`SWUSim/Custom/BotDeckLabels.json` and therefore from the picker. Accepted deliberately (owner, 2026-09-29)
rather than widening the glob ahead of the category work — a flat picker mixing tournament lists with
creator decks and no way to tell them apart is worse than the decks being temporarily unavailable.

## Naming

⚠ **The `meta-2026-09` convention (`<leader-title>_<set>_<base-archetype>`) is NOT sufficient here** and
these files are exempt from the 2026-09-29 rename. Two creators on the same archetype would collide, and
the display name would drop the attribution that is the whole reason the deck is here — `krennic_ninin`
and the melee `director-krennic_law_blue-splash` already derive the *identical* display name
"Director Krennic (LAW) Blue Splash", because the convention abstracts both Coaxium Mine and Daimyo's
Palace to "Blue Splash". An attribution component (a `_<creator>` suffix, or a stored display-name
override) is needed before this directory holds more than one deck. Unresolved; decide it with the
category work.

## Permission

A creator's list only belongs here with that creator's agreement, and the file header must say who
contributed it and record that agreement. Same-day note: "Star Wars Dad" has public lists and has agreed to
their use for Arenabot — not yet added.

| file | contributor | source |
|---|---|---|
| krennic_ninin | ninin (repo owner) | https://swustats.net/deck/VvpMoWqQWdRh (deck 108757) |
