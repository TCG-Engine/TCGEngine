# SWUSim — Sync Preview Images

Mirrors the `mock_` preview card art of **playable** preview cards from `AppCore/SWU/Images/WebpImages/` into
`SWUSim/PreviewsImplemented/`, ready for a public "Previews" page that shows which preview cards can
actually be played. Wraps the one-time tool `SWUSim/DevTools/sync-preview-images.php`.

Run it after implementing any preview card, after importing new previews with `zzPreviewTool.php`, and
after deleting a mock once official data lands.

## Usage

Host PHP is not available in this environment — run it inside the swusim container:

```
docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 \
  php -d xdebug.mode=off SWUSim/DevTools/sync-preview-images.php
```

Preview first (writes nothing, prints the full per-file decision):

```
… php -d xdebug.mode=off SWUSim/DevTools/sync-preview-images.php --dry
```

Flags:

- `--dry` — report only.
- `--all` — copy **every** `mock_` image regardless of implementation status (a "all previews imported"
  gallery rather than a "what you can play" one).
- `--set=HMW` — restrict both the copy and the prune to one set. Other sets in the destination are left
  untouched, so per-set runs compose.
- `--quiet` — summary line only.

Output is a per-file line (`copied` / `updated` / `pruned` / `skipped`) plus a summary. `skipped` always
names the card and why, so an unexpectedly missing preview is self-explaining.

## What counts as "playable"

Deliberately the exact **inverse of "would `scaffold-cards.php` propose a stub for this card?"**, so the
two tools can never drift apart. A card is included when it is:

- a **token** (`_T##` — handled generically by the engine), or
- **vanilla / keyword-only**, i.e. auto-wired by the dictionaries (e.g. HMW_019 Dune Sea), or
- **referenced by a QUOTED CardID** anywhere under `SWUSim/Custom/` — a per-card file, a monolith, or an
  engine file (a `GameLogic` passive, a `CombatLogic` reactive hook, a `KeywordEffects` grant).

⚠ Data files that merely LIST CardIDs — `CardMocks.php`, `CardTraitSupplement.php` — are excluded via
their `SCAFFOLD-IGNORE` marker. Without that they would mark every mocked card "implemented" and the
page would advertise cards that silently do nothing in game. **Any new data file listing CardIDs must
carry the marker.** Quoting is required, so an unquoted `// HMW_206` header comment never counts.

A reprint counts as playable when its canonical (earliest) printing is implemented, matching how
`cards/<set>/` folds reprints into one file.

## It is a MIRROR, not an append

Files in the destination are **pruned** when their card is no longer playable, or when the source art is
gone (what the preview-cleanup path does once a card gets official data). Only `mock_*.webp` names are
ever considered — anything else in the directory is left alone.

Copies are decided by **content hash**, not mtime, so re-importing a preview that produced identical
bytes reports `unchanged` instead of churning the directory.

Both faces are carried: `mock_<CID>.webp` and `mock_<CID>_back.webp` (leaders). Filenames keep the
`mock_` prefix so the page can map file → CardID directly, and so provenance stays obvious.

## Who consumes it

`SharedUI/Sites/SWUSim/Previews.php` — the public **Previews** page, linked from the main-menu nav bar
(left of "Support"; the entry lives in `SharedUI/Sites/SWUSim/SiteDef.php`). The page is a plain directory
listing of this mirror, grouped per set into Leaders / Bases / Units, Events, Upgrades, with titles read
from the dictionaries. It deliberately does NOT re-derive implementation status — this tool owns that
judgement, so the page can never advertise a card that does nothing in game. Adding a card to the mirror
is all it takes to publish it.

## ⚠ The directory is gitignored — regenerate after deploy

`SWUSim/PreviewsImplemented/` duplicates bytes already tracked in `WebpImages/mock_*.webp` and grows with
every preview, so it is generated rather than committed (see the note in `.gitignore`). **A Previews page
on prod will be empty until this tool runs there** — treat it like the other generated artifacts and run
it post-deploy. If that turns out to be a nuisance, tracking it instead is a one-line `.gitignore` flip.

Zero-copy alternative, if the page ever wants it: the page could read `WebpImages/mock_*` directly and
apply the same playability rule at render time, and this mirror becomes unnecessary.

## Verify

```
ls SWUSim/PreviewsImplemented/
```

Cross-check against the set plan: the CardIDs present should equal the `### Already Done` line of
`SWUSim/docs/<set>-implement.md` (expanded to two files per leader). A mismatch means either the plan's
Done list is stale or a card was implemented without a quoted CardID under `Custom/` — both worth a look,
since the second also hides the card from `scaffold-cards.php`.
