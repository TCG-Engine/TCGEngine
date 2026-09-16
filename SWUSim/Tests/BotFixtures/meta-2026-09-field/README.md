# Field fixtures — Premier, September 2026

Every archetype with **2+ entries** across the twenty melee.gg Premier events in
`docs/superpowers/research/2026-09-premier-meta/` that the curated `meta-2026-09/` set does not already cover:
61 decks, each the best-finishing legal list of its archetype. Added 2026-09-15 at the owner's request
("get every deck list … no matter how little they saw play").

**An archetype is leader + base COLOUR + base TYPE** (30HP / Force / Splash / a named rare base), not the base's
name — owner, 2026-09-15: "Fortress of the Great Mothers is just blue 30hp", as are Shield Generator Complex and
Nevarro City, Restored. Note there are two Nevarro City bases: the blue one is "Restored", the green one is not.

⚠ **The styles here are NOT reviewed.** Each header says how its style was chosen: *inherited* from an
owner-labelled fixture with the same leader (and colour where possible), or *auto* from deck shape (a rule that
reproduced 16 of 22 owner labels — it separates control well but reads low-curve midrange as aggro). Flavours are
empty: the flavour registry (`SWUSim/Custom/BotFlavours.php`) is keyed by the owner's archetype labels.

**Minimum deck size is per BASE** (owner, 2026-09-15): 50, **+10 on JTL_024 Data Vault**, **−5 on JTL_025 Thermal
Oscillator** — the only two bases that change it. A flat 50 first dropped Rio Durant · Red Thermal Oscillator, whose
45-card lists are legal, and would have let a Data Vault deck through at 58.
`SWUSim/DevTools/check_fixture_sizes.php` checks every fixture in this repo against its base's minimum.

**Two archetypes are still skipped**, both for the same reason: every Leia Organa · Blue Splash list converts to 48
of its 50 cards and every Kanan Jarrus · Red Splash list to 47, because `APIs/MeleeLinkToJson.php` silently drops
cards whose names it cannot match — in both cases "Zeb Orellios", melee's spelling of **Zeb Orrelios**
(SOR_146 / LAW_045 / ASH_161). The fix belongs in `SWUDeck/Custom/CardIdentifiers.php` (`FindCardSetCode`) and hits
ordinary deck imports too, not just fixtures.

This set is **not** the strength-test gate set: `strength_test.sh` takes a fixture directory, and the gate stays on
the 22 curated decks so a gate stays ~17 minutes. Use this set for held-out evaluation and wider training
(`rl_train.py --decks`).

| File | Archetype | Entries | Match win | Style | Style source |
|---|---|---:|---:|---|---|
| `control_directorkrennic_blue` | Director Krennic · Blue 30HP | 112 | 54.3% | control | inherited |
| `normal_mothertalzin_red_force` | Mother Talzin · Red Force | 28 | 47.7% | normal | inherited |
| `aggro_greefkarga_red` | Greef Karga · Red 30HP | 26 | 46.1% | aggro | owner |
| `normal_lukeskywalker_green_datavault` | Luke Skywalker · Green Data Vault | 26 | 47.4% | normal | owner |
| `aggro_bobafett_blue` | Boba Fett · Blue 30HP | 21 | 47.6% | aggro | inherited |
| `aggro_ezrabridger_yellow` | Ezra Bridger · Yellow 30HP | 16 | 40.9% | aggro | owner |
| `normal_admiralpiett_red` | Admiral Piett · Red 30HP | 15 | 50.0% | normal | inherited |
| `aggro_riodurant_red_thermaloscillator` | Rio Durant · Red Thermal Oscillator | 9 | 42.3% | aggro | auto |
| `normal_shinhati_red` | Shin Hati · Red 30HP | 8 | 38.8% | normal | auto |
| `normal_thearmorer_yellow` | The Armorer · Yellow 30HP | 7 | 35.9% | normal | inherited |
| `normal_obiwankenobi_blue_force` | Obi-Wan Kenobi · Blue Force | 6 | 47.3% | normal | inherited |
| `aggro_ahsokatano_blue_splash` | Ahsoka Tano · Blue Splash | 5 | 44.6% | aggro | inherited |
| `aggro_darthvader_yellow_splash` | Darth Vader · Yellow Splash | 5 | 33.3% | aggro | inherited |
| `normal_grandadmiralsloane_blue` | Grand Admiral Sloane · Blue 30HP | 5 | 58.3% | normal | auto |
| `normal_leiaorgana_green_datavault` | Leia Organa · Green Data Vault | 5 | 57.6% | normal | auto |
| `normal_sabinewren_green_datavault` | Sabine Wren · Green Data Vault | 5 | 61.4% | normal | owner |
| `aggro_bobafett_green_datavault` | Boba Fett · Green Data Vault | 4 | 61.5% | aggro | inherited |
| `control_grandadmiralthrawn_red_splash` | Grand Admiral Thrawn · Red Splash | 4 | 40.9% | control | inherited |
| `control_landocalrissian_blue_colossus` | Lando Calrissian · Blue Colossus | 4 | 56.8% | control | inherited |
| `normal_asajjventress_red` | Asajj Ventress · Red 30HP | 4 | 46.4% | normal | owner |
| `normal_darthvader_blue` | Darth Vader · Blue 30HP | 4 | 45.5% | normal | owner |
| `aggro_ahsokatano_red` | Ahsoka Tano · Red 30HP | 3 | 47.1% | aggro | inherited |
| `aggro_bobafett_yellow` | Boba Fett · Yellow 30HP | 3 | 36.8% | aggro | inherited |
| `aggro_chewbacca_yellow` | Chewbacca · Yellow 30HP | 3 | 34.2% | aggro | inherited |
| `aggro_colonelyularen_red` | Colonel Yularen · Red 30HP | 3 | 33.3% | aggro | owner |
| `aggro_greefkarga_green` | Greef Karga · Green 30HP | 3 | 40.5% | aggro | inherited |
| `aggro_poedameron_green_datavault` | Poe Dameron · Green Data Vault | 3 | 52.5% | aggro | auto |
| `aggro_rosetico_yellow` | Rose Tico · Yellow 30HP | 3 | 47.4% | aggro | owner |
| `aggro_slymoore_red` | Sly Moore · Red 30HP | 3 | 55.0% | aggro | owner |
| `aggro_theclient_red` | The Client · Red 30HP | 3 | 43.8% | aggro | auto |
| `control_admiralpiett_blue_colossus` | Admiral Piett · Blue Colossus | 3 | 61.9% | control | inherited |
| `control_dedrameero_blue` | Dedra Meero · Blue 30HP | 3 | 38.9% | control | inherited |
| `control_grandadmiralthrawn_red` | Grand Admiral Thrawn · Red 30HP | 3 | 40.6% | control | auto |
| `normal_baylanskoll_red` | Baylan Skoll · Red 30HP | 3 | 42.1% | normal | owner |
| `normal_ezrabridger_blue_splash` | Ezra Bridger · Blue Splash | 3 | 35.3% | normal | auto |
| `normal_lukeskywalker_green` | Luke Skywalker · Green 30HP | 3 | 38.2% | normal | inherited |
| `normal_mothertalzin_green_force` | Mother Talzin · Green Force | 3 | 52.9% | normal | inherited |
| `normal_obiwankenobi_yellow_force` | Obi-Wan Kenobi · Yellow Force | 3 | 37.5% | normal | inherited |
| `normal_quigonjinn_green_force` | Qui-Gon Jinn · Green Force | 3 | 26.5% | normal | auto |
| `aggro_ahsokatano_blue_allianceoutpost` | Ahsoka Tano · Blue Alliance Outpost | 2 | 42.3% | aggro | inherited |
| `aggro_darthvader_red` | Darth Vader · Red 30HP | 2 | 53.8% | aggro | inherited |
| `aggro_ezrabridger_yellow_splash` | Ezra Bridger · Yellow Splash | 2 | 33.3% | aggro | auto |
| `aggro_majorvonreg_yellow` | Major Vonreg · Yellow 30HP | 2 | 70.0% | aggro | auto |
| `aggro_poedameron_green` | Poe Dameron · Green 30HP | 2 | 26.7% | aggro | auto |
| `aggro_theclient_blue` | The Client · Blue 30HP | 2 | 11.1% | aggro | owner |
| `aggro_thirdsister_neutral_lakecountry` | Third Sister · Neutral Lake Country | 2 | 58.3% | aggro | owner |
| `aggro_tobiasbeckett_green` | Tobias Beckett · Green 30HP | 2 | 38.5% | aggro | owner |
| `aggro_tobiasbeckett_red` | Tobias Beckett · Red 30HP | 2 | 40.0% | aggro | owner |
| `aggro_wedgeantilles_yellow` | Wedge Antilles · Yellow 30HP | 2 | 22.7% | aggro | auto |
| `control_chancellorpalpatine_red` | Chancellor Palpatine · Red 30HP | 2 | 38.5% | control | auto |
| `control_directorkrennic_yellow` | Director Krennic · Yellow 30HP | 2 | 33.3% | control | inherited |
| `control_grandadmiralthrawn_yellow_force` | Grand Admiral Thrawn · Yellow Force | 2 | 46.2% | control | inherited |
| `normal_grandadmiralsloane_blue_splash` | Grand Admiral Sloane · Blue Splash | 2 | 56.7% | normal | owner |
| `normal_jabbathehutt_blue_splash` | Jabba the Hutt · Blue Splash | 2 | 64.3% | normal | owner |
| `normal_jabbathehutt_red` | Jabba the Hutt · Red 30HP | 2 | 30.8% | normal | owner |
| `normal_kyloren_green_datavault` | Kylo Ren · Green Data Vault | 2 | 54.2% | normal | auto |
| `normal_lamasu_green_datavault` | Lama Su · Green Data Vault | 2 | 20.0% | normal | auto |
| `normal_landocalrissian_green_datavault` | Lando Calrissian · Green Data Vault | 2 | 50.0% | normal | owner |
| `normal_lukeskywalker_yellow` | Luke Skywalker · Yellow 30HP | 2 | 33.3% | normal | auto |
| `normal_moffgideon_blue` | Moff Gideon · Blue 30HP | 2 | 50.0% | normal | auto |
| `normal_sabinewren_yellow` | Sabine Wren · Yellow 30HP | 2 | 42.9% | normal | auto |
