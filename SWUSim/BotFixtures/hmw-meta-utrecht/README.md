# HMW meta — Utrecht fixtures

One real-deck self-play fixture per archetype of **melee.gg 492213 (Star Wars: Unlimited Regional Championship Utrecht - Premier Main Event, 2026-11-01)** — 789 players, 766 public lists. Built 2026-11-02 at the owner's
request: the best-placed list of each archetype in the event's **top 64** (ranks are the final standings). It replaces
`hmw-meta-2026-09/` (lists from the earlier small HMW events) as the **Arenabot "HMW Meta Utrecht" pre-con group**; that folder stays
in the repo for the tests and tools that read it. Lists come from melee via `APIs/MeleeLinkToJson.php` (rootName=SWUDeck), converted by
`SWUSim/DevTools/rl/meta_baseline/convert.php`, which checks each against its base minimum. Archetype names: `SWUBotFixtureFileName`
(see `hmw-meta-2026-09/README.md`).

**Style:** where `hmw-meta-2026-09/` has the same archetype, its owner label (and `# DeckStyle:` / `# Flavours:`) carries over;
otherwise the classifier's read, marked "AWAITING THE OWNER'S CHECK" in the header.

| # | Record | Fixture | Style | From |
|---|---|---|---|---|
| 1 | 13-2-0 | `mother-talzin_lof_mystic-monastery` — Mother Talzin (LOF) Mystic Monastery | hyperaggro | classifier |
| 2 | 12-2-1 | `wicket_hmw_green-splash` — Wicket (HMW) Green Splash | hyperaggro | classifier |
| 3 | 11-2-2 | `doctor-hemlock_hmw_yellow_naboo` — Doctor Hemlock (HMW) Yellow (Naboo) | softcontrol | owner |
| 5 | 10-1-3 | `director-krennic_law_blue` — Director Krennic (LAW) Blue | softcontrol | owner |
| 6 | 11-3-0 | `han-solo_jtl_green` — Han Solo (JTL) Green | softaggro | classifier |
| 7 | 10-2-2 | `director-krennic_law_blue_naboo` — Director Krennic (LAW) Blue (Naboo) | midrange | owner |
| 10 | 10-2-1 | `lando-calrissian_law_blue_naboo` — Lando Calrissian (LAW) Blue (Naboo) | softcontrol | owner (same leader + base colour) |
| 14 | 10-3-0 | `maul_hmw_blue` — Maul (HMW) Blue | softcontrol | owner |
| 15 | 10-3-0 | `dedra-meero_sec_colossus` — Dedra Meero (SEC) Colossus | hardcontrol | owner |
| 16 | 10-3-0 | `mother-talzin_lof_yellow-force` — Mother Talzin (LOF) Yellow Force | midrange | owner |
| 18 | 10-3-0 | `ahsoka-tano_ash_yellow-splash` — Ahsoka Tano (ASH) Yellow Splash | softaggro | owner |
| 20 | 10-3-0 | `ahsoka-tano_ash_alliance-outpost` — Ahsoka Tano (ASH) Alliance Outpost | hyperaggro | classifier |
| 30 | 9-3-1 | `director-krennic_law_blue_kashyyyk` — Director Krennic (LAW) Blue (Kashyyyk) | midrange | owner (same leader + base colour) |
| 31 | 9-3-1 | `admiral-piett_jtl_blue-splash` — Admiral Piett (JTL) Blue Splash | midrange | classifier |
| 33 | 9-3-1 | `kazuda-xiono_jtl_blue` — Kazuda Xiono (JTL) Blue | softaggro | classifier |
| 36 | 9-3-1 | `tarfful_hmw_blue_kashyyyk` — Tarfful (HMW) Blue (Kashyyyk) | midrange | owner (same leader + base colour) |
| 38 | 9-4-0 | `ahsoka-tano_ash_yellow` — Ahsoka Tano (ASH) Yellow | hyperaggro | owner |
| 39 | 9-4-0 | `ahsoka-tano_ash_nabat-village` — Ahsoka Tano (ASH) Nabat Village | softaggro | classifier |
| 41 | 9-4-0 | `boba-fett_jtl_blue_endor` — Boba Fett (JTL) Blue (Endor) | hyperaggro | classifier |
| 47 | 9-4-0 | `darth-maul_lof_blue-force` — Darth Maul (LOF) Blue Force | midrange | classifier |
| 48 | 9-4-0 | `doctor-hemlock_hmw_data-vault` — Doctor Hemlock (HMW) Data Vault | softcontrol | owner |
| 49 | 9-4-0 | `darth-vader_jtl_yellow` — Darth Vader (JTL) Yellow | hyperaggro | classifier |
| 50 | 9-4-0 | `greef-karga_ash_red_tatooine` — Greef Karga (ASH) Red (Tatooine) | softaggro | classifier |
| 62 | 8-4-1 | `boba-fett_jtl_lake-country` — Boba Fett (JTL) Lake Country | hyperaggro | classifier |
