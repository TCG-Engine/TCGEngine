# November 2026 Meta (Pre-Meta Update) fixtures

One real-deck self-play fixture per archetype across **two events**, built 2026-11-08 at the owner's request as the
**Arenabot "November 2026 Meta (Pre-Meta Update)" pre-con group**. It supersedes `hmw-meta-utrecht/` (Utrecht only) in the picker; that
folder stays in the repo, hidden from the menu, like `hmw-meta-2026-09/`. "Pre-Meta Update" = before the banlist announced for 2026-11-09.

- **melee 492213** — Star Wars: Unlimited Regional Championship Utrecht - Premier Main Event (2026-11-01, 789 players, 766 public lists)
- **melee 498211** — Sector Qualifier Baltimore Play Event - Saturday (2026-11-08, 485 players, 469 public lists)

**Pick rule:** the top 8.1% of each event (Utrecht's top-64 share: Utrecht top 64, Baltimore top 39 — 103 lists), then the best-placed list
of each archetype, placing compared across events as rank / event size. 29 archetypes: the 24 of the Utrecht group (6 of them now take
the better-placed Baltimore list) plus 5 new ones from Baltimore. Lists come from melee via `APIs/MeleeLinkToJson.php` (rootName=SWUDeck),
converted by `SWUSim/DevTools/rl/meta_baseline/convert.php`, which checks each against its base minimum. Archetype names:
`SWUBotFixtureFileName` (see `hmw-meta-2026-09/README.md`).

**Style:** an archetype already in `hmw-meta-utrecht/` keeps that file's labels verbatim (`# Archetype:` / `# Style:` / `# Flavours:`);
a new one takes the owner's label of the same archetype in `hmw-meta-2026-09/` where there is one, otherwise the classifier's read, marked
"AWAITING THE OWNER'S CHECK" in the header (15 of the 29). The HMW meta set (`meta-baselines/hmw/`) holds every list of both events.

| # | Event | Record | Fixture | Style | From |
|---|---|---|---|---|---|
| 1 | Utrecht | 13-2-0 | `mother-talzin_lof_mystic-monastery` — Mother Talzin (LOF) Mystic Monastery | hyperaggro | classifier |
| 1 | Baltimore | 13-2-0 | `doctor-hemlock_hmw_yellow_naboo` — Doctor Hemlock (HMW) Yellow (Naboo) | softcontrol | owner |
| 2 | Utrecht | 12-2-1 | `wicket_hmw_green-splash` — Wicket (HMW) Green Splash | hyperaggro | classifier |
| 5 | Utrecht | 10-1-3 | `director-krennic_law_blue` — Director Krennic (LAW) Blue | softcontrol | owner |
| 6 | Utrecht | 11-3-0 | `han-solo_jtl_green` — Han Solo (JTL) Green | softaggro | classifier |
| 4 | Baltimore | 11-3-0 | `director-krennic_law_blue_naboo` — Director Krennic (LAW) Blue (Naboo) | midrange | owner |
| 5 | Baltimore | 10-3-0 | `luke-skywalker_ash_green_kashyyyk` — Luke Skywalker (ASH) Green (Kashyyyk) | midrange | owner |
| 6 | Baltimore | 10-3-0 | `ahsoka-tano_ash_yellow-splash` — Ahsoka Tano (ASH) Yellow Splash | softaggro | owner |
| 10 | Utrecht | 10-2-1 | `lando-calrissian_law_blue_naboo` — Lando Calrissian (LAW) Blue (Naboo) | softcontrol | owner (same leader + base colour) |
| 8 | Baltimore | 9-3-1 | `tarfful_hmw_colossus` — Tarfful (HMW) Colossus | midrange | classifier |
| 14 | Utrecht | 10-3-0 | `maul_hmw_blue` — Maul (HMW) Blue | softcontrol | owner |
| 15 | Utrecht | 10-3-0 | `dedra-meero_sec_colossus` — Dedra Meero (SEC) Colossus | hardcontrol | owner |
| 16 | Utrecht | 10-3-0 | `mother-talzin_lof_yellow-force` — Mother Talzin (LOF) Yellow Force | midrange | owner |
| 20 | Utrecht | 10-3-0 | `ahsoka-tano_ash_alliance-outpost` — Ahsoka Tano (ASH) Alliance Outpost | hyperaggro | classifier |
| 13 | Baltimore | 9-3-0 | `ahsoka-tano_ash_yellow` — Ahsoka Tano (ASH) Yellow | hyperaggro | owner |
| 15 | Baltimore | 9-3-0 | `boba-fett_jtl_blue_endor` — Boba Fett (JTL) Blue (Endor) | hyperaggro | classifier |
| 17 | Baltimore | 9-3-0 | `darth-maul_lof_blue-force` — Darth Maul (LOF) Blue Force | midrange | classifier |
| 30 | Utrecht | 9-3-1 | `director-krennic_law_blue_kashyyyk` — Director Krennic (LAW) Blue (Kashyyyk) | midrange | owner (same leader + base colour) |
| 19 | Baltimore | 9-3-0 | `luke-skywalker_ash_data-vault` — Luke Skywalker (ASH) Data Vault | midrange | classifier |
| 31 | Utrecht | 9-3-1 | `admiral-piett_jtl_blue-splash` — Admiral Piett (JTL) Blue Splash | midrange | classifier |
| 33 | Utrecht | 9-3-1 | `kazuda-xiono_jtl_blue` — Kazuda Xiono (JTL) Blue | softaggro | classifier |
| 21 | Baltimore | 9-3-0 | `lando-calrissian_law_blue-splash_tatooine` — Lando Calrissian (LAW) Blue Splash (Tatooine) | midrange | classifier |
| 36 | Utrecht | 9-3-1 | `tarfful_hmw_blue_kashyyyk` — Tarfful (HMW) Blue (Kashyyyk) | midrange | owner (same leader + base colour) |
| 39 | Utrecht | 9-4-0 | `ahsoka-tano_ash_nabat-village` — Ahsoka Tano (ASH) Nabat Village | softaggro | classifier |
| 48 | Utrecht | 9-4-0 | `doctor-hemlock_hmw_data-vault` — Doctor Hemlock (HMW) Data Vault | softcontrol | owner |
| 49 | Utrecht | 9-4-0 | `darth-vader_jtl_yellow` — Darth Vader (JTL) Yellow | hyperaggro | classifier |
| 50 | Utrecht | 9-4-0 | `greef-karga_ash_red_tatooine` — Greef Karga (ASH) Red (Tatooine) | softaggro | classifier |
| 36 | Baltimore | 8-4-0 | `maul_hmw_blue_naboo` — Maul (HMW) Blue (Naboo) | softcontrol | owner |
| 62 | Utrecht | 8-4-1 | `boba-fett_jtl_lake-country` — Boba Fett (JTL) Lake Country | hyperaggro | classifier |
