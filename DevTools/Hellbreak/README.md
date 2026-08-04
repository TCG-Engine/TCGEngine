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

The importer also accepts the public OneDrive URL, but Microsoft may require an interactive
session for downloads. A locally downloaded `.xlsx` is therefore the reliable workflow.

Constructed deck-size and copy-limit rules have not been published by Hellbreak. The current
deck editor enforces the known one-Monster/one-Location slots and keeps the main deck
permissive until those rules are official.
