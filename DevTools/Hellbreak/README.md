# Hellbreak card import

The public community workbook is the source snapshot for the Hellbreak apps. Download the
workbook as `.xlsx`, then run:

```powershell
php DevTools/Hellbreak/import-workbook.php --source="C:\path\to\Hellbreak.xlsx"
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

`extract-card-face-review.ps1` runs local Windows OCR over every usable front that is not yet
manually reviewed and writes `HellbreakSim/CardData/CardFaceReviewQueue.json`. Queue records are
never promoted into gameplay fields: they retain OCR text, identity confidence, source image, and
hash with `needs_review` status until a visual review moves them into `ReviewedCardFaces.json`.
The current reviewed set contains all 147 playable image-backed cards. No playable front
awaits field-level visual review; one source (`DOT_440`) is rejected because it is a multi-card
convention poster.

The importer also accepts the public OneDrive URL, but Microsoft may require an interactive
session for downloads. A locally downloaded `.xlsx` is therefore the reliable workflow.

Constructed deck-size and copy-limit rules have not been published by Hellbreak. The current
deck editor enforces the known one-Monster/one-Location slots and keeps the main deck
permissive until those rules are official.
