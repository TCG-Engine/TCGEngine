# SWU-PGN reader fixtures

Inputs and expected outputs for `DevTools/tdd-regression/test_swupgn_vectors.php`, which checks the
PHP reader in `AppCore/SWU/SwuPgn/` against the SWU-PGN/1.0 standard.

| file | what it is |
|---|---|
| `minimal` `organic` `upgrades` `pilot` `capture` `.swupgn` | the spec's five normative test vectors (spec §20) |
| `*.fold.json` / `*.render.txt` | the vectors' normative board and story — the spec requires a reader to reproduce them exactly |
| `real-6r-undo.swupgn` | a full recorded 6-round game with an undo |
| `real-7r.swupgn` | a full recorded 7-round game from an earlier writer — its keyframe gate reports 6 mismatches |
| `legacy-sample.swupgn` | an early-format sample: no `%%% STORY`, 6 keyframe mismatches |
| `*.expected.json` | what the format's reference reader returns for each `.swupgn` (below) |

The only edit made to the `.swupgn` files is the value of the `[Engine]` header tag. No reader reads
that tag.

## `*.expected.json`

Each file holds the output of running the format's own reference reader over the `.swupgn` next to it:

| key | value |
|---|---|
| `issues` | `validate(text).issues` |
| `fold` | `fold(events)`, as canonical JSON |
| `render` | `render(doc)`, with no name resolver, so the file's `%%% CARDS` names the cards |
| `storyMatchesRender` | whether the file's own `%%% STORY` equals `render(doc)` after trimming |
| `mismatches` | `checkKeyframes(events).mismatches` |
| `states` | `[seq, md5(canonical JSON of stateAt(events, seq))]` for every event, in file order |

**Canonical JSON** sorts object keys and writes an empty object as `[]`, because a PHP array decoded
from JSON cannot tell `{}` from `[]`. `swupgnCanon()` in `../swupgn_test_helpers.php` is the PHP side
of the same rule.

To add a fixture or regenerate one, run the reference reader over the new file and write the same six
keys. If the PHP reader disagrees with the reference reader, the reference reader is right (spec §2).
