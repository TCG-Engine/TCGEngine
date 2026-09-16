# Hellbreak card import

The public community workbook is the source snapshot for the Hellbreak apps. Select
`HellbreakSim` in `zzCodeGeneratorMain.php` and run the build pipeline. The pipeline downloads
the shared OneDrive workbook, imports it, rebuilds the shared card dictionaries, and regenerates
both the simulator and deck-editor runtimes. A locally downloaded `.xlsx` can be selected as an
override when OneDrive is unavailable.

The equivalent CLI workflow is:

```powershell
php DevTools/Hellbreak/import-workbook.php
$env:DEVENV='true'
php zzCardCodeGenerator.php rootName=HellbreakSim
php zzGameCodeGenerator.php rootName=HellbreakSim
php zzGameCodeGenerator.php rootName=HellbreakDeck
```

The importer detects common header variants, normalizes rows into
`HellbreakSim/GeneratedCode/cardArrayCache.json`, and extracts embedded card images into
`HellbreakSim/WebpImages`, `concat`, and `crops`. `HellbreakDeck` declares
`AssetReflection: HellbreakSim`, so the editor uses these assets without maintaining a second
image set.

Each import also writes `HellbreakSim/GeneratedCode/HellbreakImportReport.json` with source-row,
field-coverage, card-type, and image-availability counts. The current checklist is an identity
and reveal tracker: it supplies collector number, type, rarity, name, aspect, blood cost,
loyalty, franchise/IP, and image links. It does not supply combat, health, resource bar,
scheme bar, traits, or rules text; those fields require a separate reviewed transcription pass.

Reviewed card-face transcriptions live in `HellbreakSim/CardData/ReviewedCardFaces.json` rather
than in the community workbook. The importer overlays these reviewed values, records the source
image SHA-256, and exposes their review status in the generated dictionaries. This keeps image
interpretation auditable and prevents a later workbook refresh from erasing reviewed gameplay data.

Loyalty is stored twice. `loyalty` stays a number, the total pip count, so deck search and sorting
keep working. `loyaltyAspects` is the per-aspect map the engine reads (`{"Feral":2}`,
`{"Cursed":1,"Feral":1}`; `{}` for no loyalty). The importer reads either a bare count plus the
aspect column ("2" + "Feral") or explicit pairs in the loyalty cell ("1 Cursed, 1 Feral"). A bare
count with several aspects is ambiguous: the import report warns, and a card-level `loyalty` map in
`ReviewedCardFaces.json` (e.g. `"loyalty": {"Cursed": 1, "Feral": 1}`) settles it for both the
generated data and the engine.

## Card IDs and variant printings

A card's ID is its base collector number, the same number HellbreakHub uses (without its `L`
suffix on locations): `DOT_062`, `DOT_167`. Other printings of the same card are variants:
borderless printings are base + 200 (`DOT_262` "Hypnotic Gaze (Borderless)"), posters and alt art
sit in the 4xx range (`DOT_429`, `DOT_455`). A variant plays exactly like its base card, so:

- The importer links a variant to its base by the workbook's name suffix ("(Borderless)",
  "(Poster)", ...), or by the `baseCards` map at the top of `ReviewedCardFaces.json` for variants
  whose name has no suffix (`"DOT_455": "DOT_167"`). Reviewed data lives on the base card only.
- Each variant gets its base card's gameplay fields; its own number, rarity and art stay. A base
  card with no art of its own borrows a variant's.
- The `baseCard` column records the link in the dictionaries, and the importer writes
  `GeneratedCode/CardBaseMap.json` (variant -> base). `Core/CardBaseMap.php` and
  `McpServer/src/cardBaseMap.ts` read it, so the CardEditor, the hosted Card Code Service, the MCP
  tools and deck import all resolve a variant ID to its base card. Abilities are only stored on the
  base card; loading or saving through a variant ID lands there, and the generator creates no
  ability rows for variants.

A collector cell like `#114 / #115` is not a variant pair: it means the card's number is one of the
two (an unrevealed card), and the importer still creates both IDs.

## Filling art gaps from the research mirror

The workbook carries no image for some cards, and many `imageSource` URLs are dead (imgur page links
rather than direct images, an expiring Discord CDN link, one row pointing at the card-anatomy
diagram), so those cards render blank and the importer retries the same failing download every run.
`import-research-art.php` fills those gaps from the local HellbreakHub mirror in
`HellbreakSim/_research/img`:

```
php DevTools/Hellbreak/import-research-art.php --dry                      # show what would change
php DevTools/Hellbreak/import-research-art.php --card=DOT_161             # one card
php DevTools/Hellbreak/import-research-art.php                            # fill gaps only
php DevTools/Hellbreak/import-research-art.php --download --prefer-better # what the admin refresh runs
```

Ticking **"Re-download the public workbook (ignore the cached copy) and refresh card art from
HellbreakHub"** in `zzCodeGeneratorMain.php` runs `--download --prefer-better` after the workbook
import. The art step is deliberately non-fatal: a hub outage must not fail the workbook import.

`--download` refreshes the mirror from hellbreakhub.com. Its file list comes from the **card
dictionary**, not the manifest, so it works on a machine that has never held a mirror — prod
included, where `_research` is gitignored and therefore absent. A file is fetched only when missing
or when its sha256 no longer matches, and a 404 is remembered, so a second run makes no requests at
all.

`--prefer-better` replaces a card's art when the hub source is genuinely wider than what is stored;
without it only gaps are filled. Nothing is ever replaced by a smaller source. Because stored art is
capped at 900px, "wider than stored" would stay true forever, so `GeneratedCode/ArtSourceMap.json`
records which hub file each card was last built from — that is what makes repeated runs converge.

**Orientation is treated as correctness, not quality.** Locations print landscape and everything else
portrait; the hub publishes some locations under both `013.webp` (landscape) and `013L.webp` (the same
art rotated), and an `L` file is the landscape original rotated +90, so it is rotated −90 on import.
A card stored the wrong way round is replaced regardless of resolution.
Images go through the importer's own `writeCardImages()`, so `WebpImages`, `concat` and `crops` are
all written; HellbreakDeck reflects the crops, and a plain file copy would leave its grid broken.
That helper also rejects a non-card aspect ratio or a blank placeholder.

Only numerically named mirror files are considered — `001.webp` → `DOT_001`, `011L.webp` → `DOT_011`
(the `L` is the hub's location suffix, not part of the ID), `001b.webp` → `DOT_001_back`. Token art
(`T1`–`T3`), foil printings (`*bloodfoil`), alt art (`179alt1`) and the hub's word-id files
(`cronewitch`, `scubadiver`, …) are excluded. The word-id files are the cards whose collector number
is still unsettled, and they are the likeliest to be redrawn rather than official art.

⚠ The mirror is a fan site's copy, and a file's number is the hub's opinion: `167.webp` is really the
DOT_455 alt-art printing. Borrowing a variant's art for a base card is what `inheritVariantImages()`
already does deliberately, but treat these images as art, not as a source for transcribing reviewed
card data.

## Card abilities from the CLI

A card's abilities are rows in the MySQL `card_abilities` table, not files in the repo, so the
browser CardEditor (`CardEditor/UI/index.html`) is normally the only way to author one.
`card-ability.php` is the headless equivalent, writing through the same repository:

```
php DevTools/Hellbreak/card-ability.php --card=DOT_049                       # print current rows
php DevTools/Hellbreak/card-ability.php --card=DOT_049 --set=abilities.json  # replace them
```

`--set` replaces the card's whole ability list, the same as saving in the editor, then runs the game
code generator and lint-checks `GeneratedCode/GeneratedMacroCode.php`. If generation or the lint
fails, the previous rows are restored and the generator re-run, so a broken ability never survives.
A variant printing resolves to its base card. `--no-generate` skips the regeneration step.

The JSON is the array the editor posts — `macroName`, `abilityType` (`macro` or `listener`),
`abilityName`, `abilityCode`, `prereqCode`, `listenerZones`, `isImplemented`. Ability bodies take
`$player` and read macro parameters from `DecisionQueueController::GetVariable()`; prereq bodies
take `$player` plus every macro parameter positionally.

These rows are not in git. To move them to production, use the **Card ability SQL** panel in
`zzCodeGeneratorMain.php`: Export SQL locally, then Import SQL on prod (which regenerates).

`extract-card-face-review.ps1` runs local Windows OCR over every usable front that is not yet
manually reviewed and writes `HellbreakSim/CardData/CardFaceReviewQueue.json`. Queue records are
never promoted into gameplay fields: they retain OCR text, identity confidence, source image, and
hash with `needs_review` status until a visual review moves them into `ReviewedCardFaces.json`.
The current reviewed set covers all 147 playable image-backed printings with 144 entries (three are
variant printings that take their base card's entry). No playable front
awaits field-level visual review; one source (`DOT_440`) is rejected because it is a multi-card
convention poster.

The importer also accepts the public OneDrive URL, but Microsoft may require an interactive
session for downloads. A locally downloaded `.xlsx` is therefore the reliable workflow.

Constructed deck-size and copy-limit rules have not been published by Hellbreak. The current
deck editor enforces the known one-Monster/one-Location slots and keeps the main deck
permissive until those rules are official.
