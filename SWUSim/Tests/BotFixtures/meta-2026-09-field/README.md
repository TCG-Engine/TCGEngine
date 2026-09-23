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
| `directorkrennic_blue` | Director Krennic · Blue 30HP | 112 | 54.3% | control | inherited |
| `mothertalzin_red_force` | Mother Talzin · Red Force | 28 | 47.7% | normal | inherited |
| `greefkarga_red` | Greef Karga · Red 30HP | 26 | 46.1% | aggro | owner |
| `lukeskywalker_green_datavault` | Luke Skywalker · Green Data Vault | 26 | 47.4% | normal | owner |
| `bobafett_blue` | Boba Fett · Blue 30HP | 21 | 47.6% | aggro | inherited |
| `ezrabridger_yellow` | Ezra Bridger · Yellow 30HP | 16 | 40.9% | aggro | owner |
| `admiralpiett_red` | Admiral Piett · Red 30HP | 15 | 50.0% | normal | inherited |
| `riodurant_red_thermaloscillator` | Rio Durant · Red Thermal Oscillator | 9 | 42.3% | aggro | auto |
| `shinhati_red` | Shin Hati · Red 30HP | 8 | 38.8% | normal | auto |
| `thearmorer_yellow` | The Armorer · Yellow 30HP | 7 | 35.9% | normal | inherited |
| `obiwankenobi_blue_force` | Obi-Wan Kenobi · Blue Force | 6 | 47.3% | normal | inherited |
| `ahsokatano_blue_splash` | Ahsoka Tano · Blue Splash | 5 | 44.6% | aggro | inherited |
| `darthvader_yellow_splash` | Darth Vader · Yellow Splash | 5 | 33.3% | aggro | inherited |
| `grandadmiralsloane_blue` | Grand Admiral Sloane · Blue 30HP | 5 | 58.3% | normal | auto |
| `leiaorgana_green_datavault` | Leia Organa · Green Data Vault | 5 | 57.6% | normal | auto |
| `sabinewren_green_datavault` | Sabine Wren · Green Data Vault | 5 | 61.4% | normal | owner |
| `bobafett_green_datavault` | Boba Fett · Green Data Vault | 4 | 61.5% | aggro | inherited |
| `grandadmiralthrawn_red_splash` | Grand Admiral Thrawn · Red Splash | 4 | 40.9% | control | inherited |
| `landocalrissian_blue_colossus` | Lando Calrissian · Blue Colossus | 4 | 56.8% | control | inherited |
| `asajjventress_red` | Asajj Ventress · Red 30HP | 4 | 46.4% | normal | owner |
| `darthvader_blue` | Darth Vader · Blue 30HP | 4 | 45.5% | normal | owner |
| `ahsokatano_red` | Ahsoka Tano · Red 30HP | 3 | 47.1% | aggro | inherited |
| `bobafett_yellow` | Boba Fett · Yellow 30HP | 3 | 36.8% | aggro | inherited |
| `chewbacca_yellow` | Chewbacca · Yellow 30HP | 3 | 34.2% | aggro | inherited |
| `colonelyularen_red` | Colonel Yularen · Red 30HP | 3 | 33.3% | aggro | owner |
| `greefkarga_green` | Greef Karga · Green 30HP | 3 | 40.5% | aggro | inherited |
| `poedameron_green_datavault` | Poe Dameron · Green Data Vault | 3 | 52.5% | aggro | auto |
| `rosetico_yellow` | Rose Tico · Yellow 30HP | 3 | 47.4% | aggro | owner |
| `slymoore_red` | Sly Moore · Red 30HP | 3 | 55.0% | aggro | owner |
| `theclient_red` | The Client · Red 30HP | 3 | 43.8% | aggro | auto |
| `admiralpiett_blue_colossus` | Admiral Piett · Blue Colossus | 3 | 61.9% | control | inherited |
| `dedrameero_blue` | Dedra Meero · Blue 30HP | 3 | 38.9% | control | inherited |
| `grandadmiralthrawn_red` | Grand Admiral Thrawn · Red 30HP | 3 | 40.6% | control | auto |
| `baylanskoll_red` | Baylan Skoll · Red 30HP | 3 | 42.1% | normal | owner |
| `ezrabridger_blue_splash` | Ezra Bridger · Blue Splash | 3 | 35.3% | normal | auto |
| `lukeskywalker_green` | Luke Skywalker · Green 30HP | 3 | 38.2% | normal | inherited |
| `mothertalzin_green_force` | Mother Talzin · Green Force | 3 | 52.9% | normal | inherited |
| `obiwankenobi_yellow_force` | Obi-Wan Kenobi · Yellow Force | 3 | 37.5% | normal | inherited |
| `quigonjinn_green_force` | Qui-Gon Jinn · Green Force | 3 | 26.5% | normal | auto |
| `ahsokatano_blue_allianceoutpost` | Ahsoka Tano · Blue Alliance Outpost | 2 | 42.3% | aggro | inherited |
| `darthvader_red` | Darth Vader · Red 30HP | 2 | 53.8% | aggro | inherited |
| `ezrabridger_yellow_splash` | Ezra Bridger · Yellow Splash | 2 | 33.3% | aggro | auto |
| `majorvonreg_yellow` | Major Vonreg · Yellow 30HP | 2 | 70.0% | aggro | auto |
| `poedameron_green` | Poe Dameron · Green 30HP | 2 | 26.7% | aggro | auto |
| `theclient_blue` | The Client · Blue 30HP | 2 | 11.1% | aggro | owner |
| `thirdsister_neutral_lakecountry` | Third Sister · Neutral Lake Country | 2 | 58.3% | aggro | owner |
| `tobiasbeckett_green` | Tobias Beckett · Green 30HP | 2 | 38.5% | aggro | owner |
| `tobiasbeckett_red` | Tobias Beckett · Red 30HP | 2 | 40.0% | aggro | owner |
| `wedgeantilles_yellow` | Wedge Antilles · Yellow 30HP | 2 | 22.7% | aggro | auto |
| `chancellorpalpatine_red` | Chancellor Palpatine · Red 30HP | 2 | 38.5% | control | auto |
| `directorkrennic_yellow` | Director Krennic · Yellow 30HP | 2 | 33.3% | control | inherited |
| `grandadmiralthrawn_yellow_force` | Grand Admiral Thrawn · Yellow Force | 2 | 46.2% | control | inherited |
| `grandadmiralsloane_blue_splash` | Grand Admiral Sloane · Blue Splash | 2 | 56.7% | normal | owner |
| `jabbathehutt_blue_splash` | Jabba the Hutt · Blue Splash | 2 | 64.3% | normal | owner |
| `jabbathehutt_red` | Jabba the Hutt · Red 30HP | 2 | 30.8% | normal | owner |
| `kyloren_green_datavault` | Kylo Ren · Green Data Vault | 2 | 54.2% | normal | auto |
| `lamasu_green_datavault` | Lama Su · Green Data Vault | 2 | 20.0% | normal | auto |
| `landocalrissian_green_datavault` | Lando Calrissian · Green Data Vault | 2 | 50.0% | normal | owner |
| `lukeskywalker_yellow` | Luke Skywalker · Yellow 30HP | 2 | 33.3% | normal | auto |
| `moffgideon_blue` | Moff Gideon · Blue 30HP | 2 | 50.0% | normal | auto |
| `sabinewren_yellow` | Sabine Wren · Yellow 30HP | 2 | 42.9% | normal | auto |
