# HMW meta 2026-09 fixtures

One real-deck self-play fixture per leader/base archetype of **melee 480807 — Nexus Open: Homeworlds** (2026-10-10, 39 players, 26 public lists),
built 2026-10-11 at the owner's request as the first HMW meta set ("for now", until a bigger HMW event exists). When an archetype
repeats, the best final standing wins (ties: more match wins). Lists come from melee via `APIs/MeleeLinkToJson.php` (rootName=SWUDeck);
every list is checked against its base minimum and the card dictionary. Built with `SWUSim/DevTools/rl/meta_baseline/` (export, fetch).

**Archetype** = leader + base: 30HP common = the colour; 28HP LOF common = "<Colour> Force"; 27HP LAW common = "<Colour> Splash"; rare bases
by full name; a 30HP base's Homeworlds trait (Naboo / Endor / Kashyyyk / Tatooine) is appended only when a card in the deck **or its
sideboard** cares about that trait (owner 2026-10-11; the shared naming code `SWUBotFixtureFileName` is called with main + sideboard here).

**Style** labels are the owner's (2026-10-11, deck by deck). The classifier agreed on 16 of 22; it read four midrange lists as soft control (Chewbacca, Luke (ASH) Kashyyyk, Tarfful, Krennic Naboo) and two soft-aggro lists as midrange (Greef Data Vault, Kazuda) — the known bias (high curve read as control, low curve read as midrange). Each header keeps the classifier's read for reference. `# Flavours:`
appears only where `SWU_BOT_FLAVOURS` already has the leader/base.

| # | Record | Fixture | Style | Lists of it |
|---|---|---|---|---|
| 4 | 5-1-0 | `doctor-hemlock_hmw_yellow_naboo` — Doctor Hemlock (HMW) Yellow (Naboo) | softcontrol | 1 |
| 5 | 4-2-0 | `luke-skywalker_jtl_data-vault` — Luke Skywalker (JTL) Data Vault | softaggro | 1 |
| 7 | 4-2-0 | `boba-fett_jtl_yellow_endor` — Boba Fett (JTL) Yellow (Endor) | hyperaggro | 1 |
| 8 | 4-2-0 | `grand-admiral-thrawn_jtl_yellow` — Grand Admiral Thrawn (JTL) Yellow | softcontrol | 1 |
| 9 | 4-2-0 | `ahsoka-tano_ash_yellow` — Ahsoka Tano (ASH) Yellow | hyperaggro | 1 |
| 13 | 4-2-0 | `chewbacca_hmw_blue_naboo` — Chewbacca (HMW) Blue (Naboo) | midrange | 1 |
| 14 | 4-2-0 | `greef-karga_ash_data-vault` — Greef Karga (ASH) Data Vault | softaggro | 3 |
| 15 | 4-2-0 | `kazuda-xiono_jtl_red` — Kazuda Xiono (JTL) Red | softaggro | 1 |
| 16 | 3-3-0 | `sabe_sec_data-vault` — Sabé (SEC) Data Vault | midrange | 1 |
| 17 | 3-3-0 | `dedra-meero_sec_colossus` — Dedra Meero (SEC) Colossus | hardcontrol | 1 |
| 20 | 3-3-0 | `poe-dameron_jtl_green_endor` — Poe Dameron (JTL) Green (Endor) | hyperaggro | 1 |
| 21 | 3-3-0 | `grand-admiral-thrawn_jtl_data-vault` — Grand Admiral Thrawn (JTL) Data Vault | softcontrol | 1 |
| 23 | 3-3-0 | `ahsoka-tano_ash_yellow_tatooine` — Ahsoka Tano (ASH) Yellow (Tatooine) | softaggro | 1 |
| 28 | 2-4-0 | `maul_hmw_blue_naboo` — Maul (HMW) Blue (Naboo) | softcontrol | 1 |
| 30 | 2-4-0 | `grand-admiral-thrawn_jtl_green_kashyyyk` — Grand Admiral Thrawn (JTL) Green (Kashyyyk) | hardcontrol | 1 |
| 32 | 2-3-0 | `mother-talzin_lof_red-force` — Mother Talzin (LOF) Red Force | midrange | 1 |
| 34 | 2-4-0 | `poggle-the-lesser_hmw_blue_endor` — Poggle the Lesser (HMW) Blue (Endor) | midrange | 1 |
| 35 | 2-4-0 | `maul_hmw_blue` — Maul (HMW) Blue | softcontrol | 1 |
| 36 | 1-5-0 | `luke-skywalker_ash_green_kashyyyk` — Luke Skywalker (ASH) Green (Kashyyyk) | midrange | 1 |
| 37 | 1-3-0 | `tarfful_hmw_blue` — Tarfful (HMW) Blue | midrange | 1 |
| 38 | 1-5-0 | `jabba-the-hutt_sec_red` — Jabba the Hutt (SEC) Red | midrange | 1 |
| 39 | 0-4-0 | `director-krennic_law_blue_naboo` — Director Krennic (LAW) Blue (Naboo) | midrange | 1 |

Not buildable: #6 NXS_VVRATHH (4-2) and #27 2Scoops_Sam (Ahsoka (ASH) Blue Splash on Daimyo's Palace) published empty lists;
13 players (including the top three) published none.
`maul_hmw_blue_naboo` gets its Naboo trait from a SIDEBOARD card only; under a main-deck-only reading it would merge with `maul_hmw_blue`.
