# Overview
general context

## Engineering rules
- **Public APIs are a contract.** Before changing any endpoint that external consumers use (anything documented in `Stats/APIs.php` or the remote frontend integration guide, and public `APIs/` / `Stats/*API.php` endpoints), determine whether the change is breaking to our consumers — altered response shape/fields, changed default values, removed params, or new required inputs. If it could break a consumer, do NOT change the default behavior: make it additive and backward-compatible (e.g. an opt-in query param that leaves existing responses byte-identical), and document the new param. Flag the risk before implementing.
- **UI changes must work in Chromium, Firefox, AND Safari.** Never sign off on a UI/CSS change from one engine alone — layout behavior diverges (e.g. `height:100%` resolves against a flex-stretched parent in Chromium but not Firefox/WebKit). Verify the change renders correctly in all three before calling it done, ideally with a Playwright `chromium` + `firefox` + `webkit` screenshot/measure pass (see the memory note on cross-browser SWUDeck verification). If a browser can't be checked, say so explicitly rather than implying full coverage.

## Decklists
To be used for deck validation flows and loading decks for games. strategy summary included after pipe symbol |
### SWUSim
Premier (as of 2026-07-15):
https://swudb.com/deck/eeFFtweXI|Midrange go wide
https://swudb.com/deck/HeEAAQjVtrhee|Midrange go tall
https://swudb.com/deck/prozLLKSsRS|Tempo control with damage
https://swudb.com/deck/aICaKTGaQd|Hero midrange "tank and heal" with Bo-Katan SEC_051 finisher
https://swudb.com/deck/LImIrpIS|Burn aggro with Cinta Kaz pilot flip plot combo
https://swudb.com/deck/oGTmUzPJmL|Midrange control with ramp
https://swudb.com/deck/PCQRTCWTgMLr|soft aggro with heavy draw and Aggressive Negotations finisher
https://swudb.com/deck/ljPCdDElmsEF|space aggro with minimal ground support
https://swudb.com/deck/rYBmXPaxDUaSY|pure space aggro with some tempo tools

Twin Suns:
https://swudb.com/deck/kWzBQPfCopFMV|combo control deck. relies on flipping Jabba first, then flipping Qi'ra to deal at least 4 damage to anything already softened
https://swudb.com/deck/oNDdHLCHkyz|double arena aggro. Kylo on the ground. Vonreg in space.
https://swudb.com/deck/UENBLWzTHT|two Cads. tempo aggro
https://swudb.com/deck/jVTCfqAe|tribal Mandalorian deck

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