# FaBSim / FaBDeck generation

FaBSim follows Talishar's import boundary: functional card ids are normalized card name plus
pitch (`whelming_gustwave_red`), while printing ids remain metadata. Source card JSON comes from
`the-fab-cube/flesh-and-blood-cards`; FaBDeck reflects FaBSim's dictionary and art corpus.

```powershell
php zzCardCodeGenerator.php rootName=FaBSim withPreview=1
php zzGameCodeGenerator.php rootName=FaBSim
php zzGameCodeGenerator.php rootName=FaBDeck
php zzTurnGenerator.php rootName=FaBSim
php SharedUI/Render/GenerateSites.php FaBSim
php SharedUI/Render/GenerateSites.php FaBDeck
```

`Schemas/FaBSim/ImportSchema.txt` defaults `downloadImages=false`, because a full 4,900-card art
sync is large. Run `php zzCardCodeGenerator.php rootName=FaBSim downloadImages=1` for an
art-worker/deployment run; subsequent ordinary dictionary
regenerations use `FaBSim/GeneratedCode/cardArrayCache.json` and do not refetch the data.

Fabrary's integration endpoint requires an API key even when the deck itself is public. Configure
the same `$FaBraryKey` used by Talishar in `APIKeys/APIKeys.php`, or set `FABRARY_API_KEY` in the
server environment. FaBSim calls Fabrary's `/prod/v1/decks/{slug}` endpoint with `x-api-key` and
understands its `identifier`, `total`, and `sideboardTotal` fields. Without a configured key the
UI reports that setup requirement instead of incorrectly claiming the deck is private.

The first gameplay slice supports shared deck import, setup, draw, pitch, arsenal, resource
payment, basic play destinations, end-of-turn cleanup, and the FaB arena zones. Card-specific
rules are intentionally added through the normal CardEditor macro workflow rather than copied
from Talishar's GPL game implementation.
