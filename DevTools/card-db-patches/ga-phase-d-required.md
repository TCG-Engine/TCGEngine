# Grand Archive Phase D database handoff

Recorded 2026-10-16. **No database changes have been applied or verified against live authored rows.** Local MySQL is unavailable, Docker cannot start, and no remote CardCodeService connection is configured. This is an authoring checklist, not an executable or verified patch.

Current behavior is supplied by maintained [GeneratedAbilityOverrides.php](../../GrandArchiveSim/Custom/GeneratedAbilityOverrides.php) and supporting Custom helpers. Preserve these overrides until regenerated database output has demonstrated parity. This list covers the current Phase D changes; it is not an inventory of every historical override or earlier patch.

## Authored-source availability — noted at the user's request

The current local database connection is unavailable and no current authored-row export has been supplied. The card-editor MCP is not available in this session. The latest schema regeneration explicitly logged `Could not generate macro code file: No such file or directory` before its generic success message; this refreshed schema output only, preserving cached macros. No live database write or authored ability migration occurred.

Keep the damage-caller and activation-target declaration migrations open until the current authored rows/revisions can be exported and regenerated. Forged Scalemail's optional choice, Multistrike and Slipstream Vault's negation clause are **not implemented** by the runtime identity fixes below. This is an explicit Phase D dependency, not a completion waiver. Do not infer successful macro regeneration from the generator's exit code.

## Rows to reconcile when the database is available

Use card IDs and macro names to find the current authored rows. Generated continuation names below identify branches of a parent authored ability; they are **not separate database rows**. Export the current `ability_code`, `prereq_code`, metadata and revisions before preparing exact patches. Do not reconstruct authored await code from lowered generated PHP.

| Card | Authored change required |
|---|---|
| Meteoric Volley `10u5ldz371` — CardActivated prerequisite, index 0 | Resolve the recorded combat attacker in the current viewer’s frame, retaining UID identity through compaction; require a live controlled champion. The maintained prerequisite delegates to `GAControlledCombatAttacker`. |
| Overpowering Defense `14hr8i5oix` — CardActivated prerequisite, index 0 | Resolve the same live controlled combat participant and require effective Ally/Champion type plus Guardian class. Preserve the existing negate-all-opposing-activations resolution. |
| Enhance Hearing `edg616r0za` — CardActivated, index 0 | Looked-at candidates must be live; accept effective Wind element membership or effective Reaction subtype membership, including Exalted Wind. Preserve the existing optional selection and bottom-deck continuation. |
| Gearstride Academy `lxnq80yu75` — entry listener | Check the entering object’s effective Ally type and Wind membership rather than raw exact Wind equality; retain imbue and source suppression gates. |
| Veiled Dash `08kuz07nk4` — CardActivated, index 0 | Choose zero to two distinct legal units, preserving Spellshroud and live-target checks. Use the maintained target/reveal flow, including optional Wind-memory reveals, actual reveal count and stable target identity. Replace the old mandatory-first-target flow. |
| Dungeon Guide `em6eEh9q8y` — Enter | In the branch currently lowered as `Enter-2`, validate the level-up material choice and call `DoMaterialize($player, $chosen, false)`. An effect-driven level up must not record the new champion as played. |
| Heavenly Guide `tkdky3sxao` — Enter, index 0 | Require level 2+, an opposing non-Spirit champion and that champion's `gaTopUnplayed` provenance before leveling up. Having lineage alone is insufficient. |
| Firetuned Automaton `lzjmwuir99` — ActivateCard prerequisite, index 0 | Except when ignoring costs, require a live other hand card with Fire membership, using the activating controller's perspective. Match the shared additional-discard cost flow. |
| Lorraine, Ascendant Wings `81gvGHkuVb` — ActivateAbility, index 0 | Author the missing ability with display `(2024): Generate Sword regalia`, prerequisite `GALorraineAscendantCanActivate(GetZoneObject($mzID))`, and body `GALorraineAscendantResolve($player)`. The maintained activation intercept pays the real **2024 reserve before resolution**; do not duplicate payment in the body, add a rest cost or discount it for tests. Regenerated ability-count/name metadata must replace the fallback only after parity checks. Inherited activation and permanent Sword permission remain runtime responsibilities. |
| Floral Arrangement `dt4YncETqE` — CardActivated, index 0 | Call `GAPutFieldGroup($player, ["bd7ozuj68m", "soporhlq2k"])` so both entries belong to one simultaneous instruction. |
| Cell Production `m6lGw6bQtb` — CardActivated, index 0 | Call `GAPutFieldGroup($player, ["qzzadf9q1v", "qzzadf9q1v"], true)` for the simultaneous rested-token group. |
| Inner Court Schemer `spijrps4ny` / Scorchfire Assassin `o4h8cfo21a` — OnAttack | Delegate to `GABeginPrepAttackChoice($player, cardID, 1 or 3)`; Scorchfire retains Assassin Class Bonus. The maintained helper binds payer/attacker UIDs and revalidates both at every answer. Preserve this behavior when retiring the final overrides. |
| Captain Archer `disqw3d0o5` — Enter | Use effective Wind membership for the looked-at ally rather than exact printed Wind equality, preserving the existing Imbue/Class Bonus and reserve-cost gates. |
| Red Hare `5du8f077ua` — OnAttack, index 0 | Use effective Fire/Tera membership and effective Unique/Human checks consistent with the maintained Pride calculation, then the existing discard/draw flow. |

### Generation affected by Greater Boon of Luxera

The naming effect's restriction belongs at each generation instruction, including after its draw reward has been used. Reconcile these authored parent abilities with `GACanGenerateCard` / `GAGenerateCard` and the maintained overrides:

| Parent card / macro | Generated card / branch |
|---|---|
| Mantle of Abyss `1ubrwubSQN` — ActivateAbility | Rile Abyss `ye7f7o5yut` |
| Bleu, Ace of Diamonds `k5Kid67duu` — Enter | Bolt `ek5r5YlFQv` |
| Polaris `41t71u4bzz` — ActivateAbility | Cosmic Bolt `vpmu6gvnta`; continuation `ActivateAbility-1` |
| Diamond Ribbon `lVvU9lpJ3M` — ActivateAbility | Bolt; continuations `ActivateAbility-1` and `-2` |
| Diana, Duskstalker `iq4d5vettc` — OnHit | Creeping Torment `zrplywc08c` |
| Gloamspire Sniper `6hjlgx72rf` — OnKill | Creeping Torment; continuation `OnKill-1` |
| Seiryuu `tf5f2n38g0`, `GKKYGv6t3R`, `fcfxhkqda6` — OnAttack | Arcane Blast `pn9gQjV3Rb`; guard only generation mode `0` in choice continuation 1. Preserve Empower and avoid dereferencing a blocked generation result. |
| Nuwa `CW73nq8jCR` — PlayCard | Refurbish `b43adsk77Y` |
| Four of Diamonds `NsnBhlVzTV` — ActivateAbility | Bolt; retain the reveal and subsequent choices even when generation is blocked (continuations `-1` / `-2`). |

Sablemere `WAodKSuGuX` already delegates to maintained `SablemereWardensGripEffect`; that helper now guards generation while retaining lash. Check the DB delegation, rather than copying its PHP into a row. Solomon `q0P2TmYGXs` needs a separate source audit; it was not implemented in this batch. This table is not proof that every generation route has been migrated.

## Changes that do not require new card rows

- Duplicitous Replication `owq8s5fefw` already registers `AddGlobalEffects($player, "owq8s5fefw")`. Its corrected entry-group/copy behavior is shared runtime logic.
- Eternal Magistrate `taug52u81v` already has Imbue registration. Material departure restrictions use runtime legality gates and the game-schema `BeforeRemove: GAMaterialCanLeave` hook; do not invent a card trigger for it.
- Material ownership, played/lineage provenance, entry events, token copies, Lorraine inheritance/payment, effective-element helpers and custom DQ handlers remain maintained engine code.
- Wildgrowth Fatestone `x2oydmfcre` and Nico, Rapture's Embrace `29lqrve8fz` element fixes are in their maintained entry helpers (`WildgrowthFatestoneOnEnterCheck` / `NicoRapturesEmbraceEnter`). Preserve the existing helper delegation on regeneration; no new card row is required for these membership corrections.
- **No SQL table migration is identified for this batch.** `BeforeRemove` changes the game schema/code generator, not the database schema. Provenance and event state use existing serialized state. Later engine designs may introduce other requirements.

## Remaining D0 source migrations need design first

Forged Scalemail needs resumable damage and caller continuation before an optional prevention prompt can be authored safely. Multistrike needs declared distinct defenders and per-defender retaliation/hit/kill processing. Slipstream Vault's separate activation-negation clause needs declared target/pending-activation support. These are unfinished engine work, not ready-to-apply database patches.

The wider generated element/combat reader audit remains open. Possessed Reaping's exact destination is now supplied by maintained runtime code and the Graveyard `BeforeRemove: GAGraveyardIdentityLeaving` hook; keep its existing helper delegation. Malignant Athame, Blastshot Pump and the bound preparation-choice helpers retain immutable delayed-event context in maintained runtime code. The inventory found 152 generated `DealDamage` call sites and 89 maintained Custom sites; these are source-site counts, not a verified patch count. Obtain canonical authored code and inspect await generation before migrating generated damage callers. A queued prompt inside synchronous damage is insufficient if its caller continues immediately.

## Apply and verify later

1. Restore the intended Grand Archive database or configure its CardCodeService connection. Export the affected rows and revisions; compare each against the maintained implementation and existing patch manifests.
2. Prepare narrowly scoped, revision-checked patches from the actual rows. Dry-run first; preserve unrelated macros, listeners, metadata and implementation flags. Do not use a whole-root delete/insert export.
3. Apply on each intended environment, re-read to confirm, then regenerate GrandArchiveSim. Check that macro code **and ability-count/name metadata** were generated. The generator can report completion after a database connection failure while only schema outputs refresh; exit status alone is insufficient.
4. Keep overrides active while validating regenerated source. Retire individual overrides only after proving the generated path itself passes the relevant fixtures and mutation checks. Run the full integration suite, GABot, boundary checks and local guards before recording migration completion.

See the [roadmap](../ga-fixtures/ROADMAP.md) for unfinished Phase D work and the [patch README](README.md) for earlier verified/unverified patch manifests. No current full-suite result is asserted by this documentation update.
