# Cards Blocked by Missing Engine Mechanics

**For currently implementable cards, see Easy.md**

## By Category

### Missing: Activated abilities from Graveyard
Voltaic Sphere (0op3nq0ymv)

### Missing: "On Ally Hit" (Triggered When Ally Takes Damage)

**Status**: OnHit macro EXISTS for the attacking unit's perspective (fires after hit), but no system for triggered abilities that fire on a defending card when IT takes damage from an attack.

Would need: A defending-unit damage event hook that dispatches passive/triggered abilities on the defending card.

Cards blocked:
- Tristan, Grim Stalker (K5luT8aRzc) — On Ally Hit: remove 3 prep counters to destroy hit ally.
- Intrepid Spearman (pal7cpvn96) — [Level 1+] When combat damage would be dealt to CARDNAME, reveal a random memory card; if wind element, prevent 3 of that damage (once per turn). This is a replacement effect that fires on the defending unit when it receives combat damage.

---



### Missing: Copy Activation

**Status**: No mechanism to copy a card's activation mid-stack. This is fundamentally complex because:
- Copying requires cloning the activation's effect context (targets, costs paid, etc.)
- Cloning to a new set of targets requires re-resolving with new context

Cards blocked:
- Rai, Mana Weaver (6ILtLfjQEe) — REST, remove 4 enlighten counters: copy target Mage Spell activation with optional new targets.

---

### Missing: Transfer Control

**Status**: No mechanism to change a card's Controller field at runtime. The Field zone has a Controller property, but no DQ action or helper changes it.

Cards blocked:
- Excalibur, Cursed Sword (4sm14RaEkg) — Class Bonus On Enter: choose a player, that player gains control of this weapon.
  - Note: "whenever you materialize, deal 2 damage to your own champion" part IS implementable.

---

### Missing: Immortality Keyword

**Status**: No flag to prevent `DoAllyDestroyed` from triggering on a unit.

Would need: A TurnEffect like `IMMORTAL` that blocks entry into DoAllyDestroyed.

Cards blocked:
- Arthur, Young Heir (GjM8b5fxqj) — On Enter: may rest Arthur, if so gains immortality until beginning of next turn. While rested, allies you control get +1 power.
  - Note: The passive power buff IS implementable.

---

### Missing: Cost Increase for Specific Card Names

**Status**: No mechanism to make cards of a given name cost more for the opponent to play. Would require:
- A per-player registry of "locked" card names and their cost modifiers
- Checks in GetDynamicCost when reserving/playing those cards
- Cleanup at end of turn

Cards blocked:
- Nia, Mistveiled Scout (PZM9uvCFai) — On Enter: look at opponent's memory, choose a card name; cards with that name cost 1 more.
  - Note: Stealth keyword IS in schema.

---

### Missing: Death Replacement Effect

**Status**: No mechanism to intercept the "unit dies" event and redirect it to banishment instead. The `DoAllyDestroyed` function moves the card to the graveyard unconditionally; there is no pre-death hook that can reroute the destination.

Would need: A pre-death hook (before the MZMove to GY) that checks for replacement effects, then calls `MZMove` to banishment instead.

Cards blocked:
- Corhazi Arsonist (0ejcyuvuxn) — "If a unit hit by CARDNAME this turn would die, banish it instead." (Also has an easy stealth ability via prep counter removal, which IS implementable.)

---

### Missing: Damage Redirection (Champion to Linked Ally)

**Status**: The `DealChampionDamage` path and the combat-damage path both unconditionally apply damage to the champion. There is no replacement-effect hook that can catch incoming champion damage and re-route it to a different target.

Would need: A pre-champion-damage hook checked in both `DealChampionDamage` and the combat resolve paths; if a redirection effect is active, call `OnDealDamage` on the linked ally and return without applying champion damage.

Cards blocked:
- Covenant of Thorns (1vt1cn1tzg) — Ally Link item. "If damage would be dealt to your champion, that damage is dealt to linked ally instead." (Class Bonus cost reduction IS implementable.)

---

### Missing: Reserve Cost Payment Trigger

**Status**: The `ReserveCard_Process` DQ handler exhausts or moves the chosen card but does not fire triggered abilities based on WHICH card was used to pay or WHAT card was being activated. Adding per-card reserve-payment triggers would require making `ReserveCard_Process` aware of both the paying card identity and the card on the EffectStack (the activation being paid for).

Would need: Pass the paying card's identity through the reserve payment flow and check registered handlers after each payment.

Cards blocked:
- Wildheart Lyre (50pcescfpw) — "[Class Bonus] Whenever you rest CARDNAME to pay for the reserve cost of a Harmony or Melody activation, put a buff counter on an Animal or Beast ally you control." (Reservable keyword IS implemented.)

---

### Missing: Non-Reserve Additional Activation Costs

**Status**: The `$additionalActivationCosts` framework only supports optional hand→memory (reserve) payments presented as YesNo. Costs of the form "banish N cards of type X from zone Y" cannot be declared through this framework and have no alternative hook.

Would need: A new mandatory or optional cost-declaration pathway that can consume cards from zones other than hand (e.g., banish from material deck), with legality gating.

Cards blocked:
- Kraal, Stonescale Tyrant (572j3oda2h) — "As an additional cost to activate, banish two preserved cards from your material deck." (Keywords Intercept/Spellshroud/True Sight/Vigor are all in schema; the On Attack reveal-and-preserve IS implementable — only the activation cost is blocked.)

---

### Missing: Runtime Subtype Addition

**Status**: `CardSubtypes($cardID)` reads directly from the generated card dictionary. There is no mechanism to extend a card's subtypes with a runtime overlay keyed to its mzID or a TurnEffect.

Would need: A subtype-override lookup (mzID → additional subtypes array) checked in `CardSubtypes` and everywhere subtypes are used (ZoneSearch, pride checks, combat bonus checks).

Cards blocked:
- Beastsoul Visage (8asbierp5k) — Ally Link item. "Linked ally gets +2 POWER, has pride 3, and is a Beast in addition to its other types." (+2 POWER and pride 3 override are implementable; the Beast subtype addition is blocked.)

---

### Missing: Alternative Activation from Material Deck with GY Cost

**Status**: `DoActivateCard` only activates from hand or the existing fast-speed zone. There is no path to activate a card from the material deck itself by paying an alternative cost (banishing GY cards). While Voltaic Sphere (existing Hard entry) activates *abilities* from the GY, Varuckan Soulknife needs to activate the card itself *from the material deck* with a GY banish condition.

Would need: An alternative activation flow for material-zone cards that checks an alternative cost in the GY, converts it to a standard activation, and routes it through `DoActivateCard`.

Cards blocked:
- Varuckan Soulknife (9ox7u6wzh9) — "[Class Bonus] [Element Bonus] You may banish three fire element cards from your graveyard to activate this card from your material deck." (Class Bonus On Kill recycling from banishment IS implementable.)

---

### Missing: Indefinite Card Transformation

**Status**: `TurnEffects` are cleared at end of turn. There is no persistent-until-removed effect layer that can change a card's type, subtype, and ability set indefinitely. Fracturize's "lasts indefinitely" clause cannot be implemented without a permanent effect registry.

Would need: A persistent-effect store (not tied to turns) that overrides `CardType`, `CardSubtypes`, and the ability lookup for a specific mzID or CardID instance.

Cards blocked:
- Fracturize (cpvn96659y) — "Target item or weapon becomes a Cleric Fractal phantasia with reservable and loses all other abilities. (This effect lasts indefinitely.)" (Floating Memory keyword IS implementable.)

---

### Missing: Face-Down Zone Banishment

**Status**: All banished cards are tracked by CardID and visible to both players via the zone state. There is no flag to mark a card as face-down (hidden from opponent) in the banishment zone.

Would need: A `FaceDown` flag on zone objects and UI masking logic (client + server) that hides the CardID from the non-owning player.

Cards blocked:
- Quicksilver Grail (cxyky280mt) — "On Enter: Banish a non-champion card from your material deck face down." / "Banish CARDNAME: You may play the banished card." The play-banished-card ability IS implementable once face-down banishment exists.

---

### Missing: Passive Universal Damage Reduction for Matching Units

**Status**: The existing `OnDealDamage` hook modifies damage on a per-call basis, but there is no active passive that scans all of a player's units and halves incoming damage for each. Implementing half-damage for every qualifying unit per damage event requires either: (a) a registered passive listener array checked every time `OnDealDamage` is called, or (b) tagging each crux unit individually — infeasible for dynamically entered units.

Would need: A registered passive-effect list (keyed by player) checked in `OnDealDamage` that can apply proportional damage reductions to specific subsets of units.

Cards blocked:
- The Majestic Spirit (tsvbgl6ffq) — "If another crux element unit you control would take damage, prevent half of that damage, rounded up." (Intercept/True Sight/Vigor and the champion spellshroud passive ARE implementable.)

---

### Missing: Per-Card Self Ability Suppression (Loses All Abilities)

**Status**: `SuppressAlly` removes the card from the field temporarily. "Loses all abilities" without leaving the field requires a flag that silences the card's keywords (pride, etc.) and blocks ability dispatch for it specifically. No such per-card "blanket suppress" TurnEffect exists; pride is checked globally via `PrideAmount()` and class bonus checks are not guarded by any per-card ability flag.

Would need: A `NO_ABILITIES` TurnEffect variant that is consulted in `PrideAmount`, `GetDynamicAbilities`, `ClassBonusActivateCostReduction`, and the `cardActivatedAbilities` dispatch to skip the card.

Cards blocked:
- Capricious Lynx (v1au7t9m4m) — Pride 4. "[Class Bonus] On Enter: CARDNAME loses all abilities until end of turn."

---

### Missing: Cross-Turn Attack Prevention

**Status**: No mechanism to block attack declarations for the remainder of a turn and then into the opponent's next turn. Attack declarations are implicit (champion selects a target); there is no pre-declaration gate currently checked. Carrying this restriction through the turn boundary (surviving recollection) requires persistent state plus cleanup in `RecollectionPhase`.

Would need: A `StoreVariable`-based "attacks blocked" flag per player, a check in the champion attack selection flow (before `DeclareChampionAttack`), and cleanup at the target player's recollection phase.

Cards blocked:
- Peaceful Reunion (wr42i6eifn) — "Activate only if you have not declared an attack this turn. Until the beginning of your next turn, you and target player can't declare attacks. Banish Peaceful Reunion."

---

### Missing: Negate Activation

**Status**: No system for countering/negating a card mid-stack after it has been placed on the EffectStack.

Would need: An effect that can remove an EffectStack entry and skip its resolution.

Cards blocked:
- Camelot, Impenetrable (R9UFbI4Fsh) — May negate wind element activations and suppress an ally.
  - Note: Domain upkeep (materialize-sacrifice) IS implemented. Only the negate activation part remains blocked.

---

### Missing: Weapons Attacking as Allies / Ally Using Weapons

**Status**: No mechanism for a weapon to declare attacks independently, or for an ally to use a weapon as an attack source.

Would need: 
- Weapons treated as attack units (subclass of attacker)
- Weapon attack declaration flow separate from ally/champion attacks
- Or: Ally can select weapon as attack type modifier

Cards blocked:
- Spirit Blade: Ensoul (CQ1bxUyi0Q) — Puts Sword weapons on field; they can attack as if allies until next end phase.
- Galahad, Court Knight (eO5wsjwRyQ) — Class Bonus: this ally can attack using Sword weapons you control.

---

### Missing: Element Restriction Lock

**Status**: No mechanism to prevent players from activating or materializing cards of specific elements for a duration.

Would need: A per-player effect registry checked in GetDynamicAbilities (for activation) and in Materialize (for materialization).

Cards blocked:
- Excalibur, Cleansing Light (qtRBz9azeZ) — Class Bonus: choose up to 2 non-norm elements; players can't play those elements until next turn.
  - Note: Destroy non-champion object part IS implementable (already partially done).

---

### Missing: Complex Unique Mechanics

**Conduit of the Mad Mage (6SXL09rEzS)**
- Needs: Cross-card trigger "whenever any Mage Spell action is activated, wake this specific ally and grant it +1 power"
- Current OnCardActivated hook is per-card, not a global listener to other units' activations
- Also needs: End-phase self-sacrifice (feasible, can add to EndPhase switch)
- **Blocker**: Cross-card CardActivated watcher (no listener pattern)

**Tome of Sacred Lightning (MyUTeqUJ0H)**
- Needs: Element Bonus: banish a Book regalia to activate from material deck, inheriting the banished card's abilities
- **Blocker**: Runtime ability inheritance not currently supported
- Note: Class Bonus (REST: banish random from memory, draw) IS implementable

**Dream Fairy (UVAb8CmjtL)**
- Needs: Return opponent's ally to their Memory (distinct from Hand)
- Needs: Ongoing name-ban "Opponents can't play cards with that ally's name while this is controlled"
- **Blockers**: 
  - Memory zone move works, but requires confirming zone exists
  - Name-lock requires per-player registry + checks in play/activate flows

**Spark Fairy (FWnxKjSeB1)**
- Needs: Attach a recurring "deal 1 unpreventable damage to your champion at recollection phase" effect to a target non-champion object, active only while Spark Fairy is controlled
- **Blocker**: Dynamically attaching recurring effects to specific objects at runtime (not currently in TurnEffect system)

**Discordia, Harp of Malice (5LoOprBJay)**
- Needs: Variable level-swing on both champions simultaneously (music counter count), then scheduled banish at next end phase
- Music counter accumulation via CardActivated trigger IS feasible
- **Blocker**: Scheduled action at future end phase (would need per-player end-phase flag + counter tracking)

**Map of Hidden Passage (2bzajcZZRD)**
- Needs: "Until end of turn, units with stealth cannot be intercepted"
- **Blocker**: Requires a new global flag read by GetValidAttackTargets to override intercept logic

**Poisoned Dagger (0D6AfZyKXh)**
- Needs: Until end of turn, that unit takes +1 extra from all damage sources
- **Blocker**: Damage amplification per-unit requires a new OnDealDamage hook to check this card's effect

**Arcane Disposition (blq7qXGvWH)**
- Needs: At beginning of next end phase, discard hand
- Draw 2 (or 3 Class Bonus) IS implementable
- **Blocker**: Scheduling a "discard hand" action at a future end phase (not currently supported; would need per-player flag + counter)

**Mana Limiter (IC3OU6vCnF)**
- Passive: cannot use enlighten counters to pay costs
- **Blocker**: Enlighten counter draw is hardcoded in GetDynamicAbilities; gating it requires additional check there
- Note: Banish-for-draw ability (when 6+ enlighten) IS implementable

**Sealed Blade (mDN1CI9IEe)**
- Needs: "Pay only using floating memory for this card's memory cost"
- **Blocker**: Requires a materialize cost constraint preventing any non-floating-memory payment
- Note: Class Bonus +1 power IS implementable

**Invoke Dominance (PLljzdiMmq)**
- Preserve (schema) — YES
- +3 level — YES
- "Can't activate non-ally cards this turn" — NO
- **Blocker**: Turn-wide activation restriction per player not enforced anywhere

**Triskit, Guidance Angel (ilW4cRlI0C)**
- Needs: May banish your champion; if so, Triskit becomes a unique champion with base level 3
- **Blocker**: Champion-to-ally transformation (and ally-to-champion promotion) not in engine

**Nimue, Cursed Touch (l52lVIFvpy)**
- Needs: Whenever you activate an action card that targets an ally, destroy that ally
- **Blocker**: Requires intercepting "does this action target an ally?" and hooking destruction before resolution; no targeting-inspector mechanism exists

**Mistbound Cutthroat (C7zFV2K7bL)**
- Needs: Level 3+: whenever another ally enters the GY from the deck (milled, not killed), return this from GY to field rested
- **Blocker**: Distinguishing GY arrivals by source (milled vs. destroyed) and triggering a GY-to-field return

**Weaponsmith (6gN5KjqRW5)**
- Needs: At beginning of recollection phase, put durability counter on target weapon
- **Blocker**: Recollection phase currently synchronous loop; adding mid-loop player choice (which weapon to target) requires upgrading to async DQ decisions

**Nullifying Lantern (urKxcUjz9a)**
- Needs: Cards in graveyards are norm element
- **Blocker**: Graveyard element override requires changes in every place that checks card element (ZoneSearch, CardElement lookups, IsHarmonizeActive, etc.)

**Swan Song / Gaia's Blessing (ymhDYTPfi1)**
- Needs: Play with top of deck revealed; may activate Animal/Beast ally cards from the top of deck
- **Blocker**: "Face-up" deck and allowing direct play from top is significant UI/engine addition

**Surveillance Stone (kk46Whz7CJ)**
- Needs: Whenever an opponent declares their third attack each turn, may banish to draw
- **Blocker**: Per-turn per-player attack-declaration counts tracked globally, then checked on each BeginCombatPhase call

**Hymn of Gaia's Grace (okDVkV1l76)**
- Needs: Glimpse 3, draw. May put an Animal/Beast ally from hand to field and redirect incoming attack to it
- **Blocker**: "Enter as interceptor mid-combat" timing is a new fast-speed play context

**Gleaming Cut (qufoIF014c)**
- Needs: On Attack: choose and reveal a card in your memory; if luxem, +2 power
- **Blocker**: "Choose and reveal from memory" is a new interaction (memories not normally visible/selectable)

---

## Notes on Engine Maturity

Since the blockers list was created, the following capabilities HAVE been added:
- ✅ **OnHit / OnKill macros** — triggered when attacks hit or kill targets
- ✅ **OnDealDamage hook** — can suppress or modify damage
- ✅ **AddTurnEffect** — per-card effects until end of turn
- ✅ **AddGlobalEffects** — global effects affecting multiple cards
- ✅ **YesNo / MZChoose decisions** — complex player input flows in abilities
- ✅ **Reveal loops** — revealed card loops with searching
- ✅ **ClassBonusActive checks** — integrated into game logic
- ✅ **Counters system** — prep, durability, buffs, enlighten, etc.
- ✅ **await syntax** — multi-step ability code with player decisions
- ✅ **DealUnpreventableDamage** — unpreventable damage handling
- ✅ **Domain card type** — activation framework, materialize-sacrifice upkeep, recollection upkeep, Right of Realm NO_UPKEEP exemption
- ✅ **Non-combat damage flag** — `OnDealDamage` now receives an explicit `$isCombat` param; all combat damage flows through `DealCombatDamage`; passive field-scan prevention for non-combat events is now implementable (used by Blanche, Sheltering Saint)
- ✅ **MZSplitAssign** — split-assign a numeric pool across multiple targets with inline +/- UI overlay. Await syntax: `$result = await $player.MZSplitAssign($targets, $amount, "tooltip")`. Returns comma-separated `mzID:amount` pairs.
- ✅ **Retaliate from non-defending position** — `CombatProceedToRetaliation` now appends any ready non-defending Lurking Assailant (`uq2r6v374c`) to the `MZMAYCHOOSE` retaliator list; `HasStealth` also covers its stealth-while-awake passive.

This has enabled many cards from the original list to become implementable. See Easy.md for those cards.
