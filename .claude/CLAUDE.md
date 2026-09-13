# Overview
general context

## Engineering rules
- **Public APIs are a contract.** Before changing any endpoint that external consumers use (anything documented in `Stats/APIs.php` or the remote frontend integration guide, and public `APIs/` / `Stats/*API.php` endpoints), determine whether the change is breaking to our consumers — altered response shape/fields, changed default values, removed params, or new required inputs. If it could break a consumer, do NOT change the default behavior: make it additive and backward-compatible (e.g. an opt-in query param that leaves existing responses byte-identical), and document the new param. Flag the risk before implementing.
- **UI changes must work in Chromium, Firefox, AND Safari.** Never sign off on a UI/CSS change from one engine alone — layout behavior diverges (e.g. `height:100%` resolves against a flex-stretched parent in Chromium but not Firefox/WebKit). Verify the change renders correctly in all three before calling it done, ideally with a Playwright `chromium` + `firefox` + `webkit` screenshot/measure pass (see the memory note on cross-browser SWUDeck verification). If a browser can't be checked, say so explicitly rather than implying full coverage.
- **Decode/encode WebP with Imagick, never GD's `imagecreatefromwebp()`/`imagewebp()`.** Prod LAMPP's PHP GD is compiled WITHOUT WebP support (`gd_info()` → `WebP Support` off), so those functions are *undefined* there and calling them is a fatal — even though dev/Docker GD has them, so it passes locally and dies on prod. Card art (`WebpImages/`) is WebP. Read WebP via Imagick (or the `SWUDeck/lib/CardImageLoader.php` helper's Imagick → `dwebp` fallback chain) and hand GD a PNG blob; other formats (jpeg/png) may use GD directly. Any GD pipeline that touches WebP must route through this — assume `imagecreatefromwebp` does not exist.
- **Per-app engine files are GENERATED and gitignored — edit the schema, not the output.** Each app's `InitialLayout.php`, `GetNextTurn.php`, `NextTurnRender.php`, `GamestateParser.php`, `ZoneAccessors.php`, `ZoneClasses.php`, and `GeneratedUI*.js` are produced by `zzGameCodeGenerator.php` from `Schemas/<App>/GameSchema.txt` and are **gitignored** (they won't commit and are overwritten on regen). To change toolbar modules (e.g. the `AssetVisibility` visibility dropdown), read-side visibility enforcement, zones, etc., edit `GameSchema.txt` (or the generator/its `Custom/` layout inputs), then regenerate with `php zzGameCodeGenerator.php rootName=<App>` (or the `zzCodeGeneratorMain.php` mod UI). Never hand-edit a generated file — the fix must live in tracked source. After any schema/generator change, **regenerate on the server post-deploy** (a stale generated file also causes a blank board). Check `.gitignore` if unsure whether a file is generated.

## Decklists
To be used for deck validation flows and loading decks for games. strategy summary included after pipe symbol |
### SWUSim
Premier (as of 2026-07-15):
https://swudb.com/deck/eeFFtweXI | Midrange go wide
https://swudb.com/deck/HeEAAQjVtrhee | Midrange go tall
https://swudb.com/deck/prozLLKSsRS | Tempo control with damage
https://swudb.com/deck/aICaKTGaQd | Hero midrange "tank and heal" with Bo-Katan SEC_051 finisher
https://swudb.com/deck/LImIrpIS | Burn aggro with Cinta Kaz pilot flip plot combo
https://swudb.com/deck/PCQRTCWTgMLr | soft aggro with heavy draw and Aggressive Negotations finisher
https://swudb.com/deck/ljPCdDElmsEF | space aggro with minimal ground support
https://swudb.com/deck/rYBmXPaxDUaSY | pure space aggro with some tempo tools

Premier Competitive (as of 2026-09-09):
https://melee.gg/Decklist/View/73b660ce-7055-4a3b-bef4-b4bd00482ebd | Krennic blue soft control/midrange control. self sacs for Credit ramp
https://melee.gg/Decklist/View/8b67d9aa-9654-42c3-8a26-b4bd00d518ba | Vader yellow space aggro
https://melee.gg/Decklist/View/41f78077-05e8-4d61-971f-b4bd003946a5 | Darth Maul blue Force tempo aggro midrange
https://melee.gg/Decklist/View/af94dfb6-2132-4b0f-bca8-b4bc01574c37 | Ahsoka blue aggro go wide plus combos
https://melee.gg/Decklist/View/8b2d5c40-77c0-4454-b4b1-b4bc00df5b5f | Leia blue aggro go tall
https://melee.gg/Decklist/View/ef250fc5-56ff-4194-98c8-b4bc007dcf85 | Mando Colossus hard control with late game bombs
https://melee.gg/Decklist/View/be414b4b-c4f9-4b90-8c23-b4bc0038748b | Luke pilot Data Vault space midrange pilot deck
https://melee.gg/Decklist/View/a8cf1f9c-221d-4b4a-8dbf-b4c300d076c3 | Lando blue midrange Credit tempo engine with early bombs


Twin Suns:
https://swudb.com/deck/kWzBQPfCopFMV|combo control deck. relies on flipping Jabba first, then flipping Qi'ra to deal at least 4 damage to anything already softened
https://swudb.com/deck/oNDdHLCHkyz|double arena aggro. Kylo on the ground. Vonreg in space.
https://swudb.com/deck/UENBLWzTHT|two Cads. tempo aggro
https://swudb.com/deck/jVTCfqAe|tribal Mandalorian deck

Padawan:
https://swudb.com/deck/jEYkqCvG|Imperial tribal
https://swudb.com/deck/eCaJBRLndKSsP
https://swudb.com/deck/CgMpEdNDTr
https://swudb.com/deck/xYvMHwbAkw

## GrandArchiveSim
https://sleeved.gg/grand-archive/decks/4f04a8b3-4ea0-42dd-9872-f00dff259d87|

## Creds
for Playwright harness checks and running through real games
### SWUSim
claudebot1:pass
claudebot2:pass
claudebot3:pass
claudebot4:pass

## SWUDeck
Drixx:pass

## GrandArchiveSim
{currently no logins}