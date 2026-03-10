# FTC Cards — Implementable with Current Engine

All 43 cards below can be implemented using existing helpers, macros, and patterns.
For cards requiring new engine development, see Hard.md.

---

## Ally Champions (Inner Lineage)

**Spirit of Serene Fire (da2ha4dk88)** — FIRE CHAMPION, Spirit
- On Enter: Glimpse 6, Draw 6 (standard `Glimpse` + `Draw` helpers).
- Lineage Release: Recover 6 (standard `RecoverChampion` via the Lineage Release macro).

**Spirit of Serene Wind (h973fdt8pt)** — WIND CHAMPION, Spirit
- Identical effect to Spirit of Serene Fire. Use the same macro pattern.

**Spirit of Serene Water (zq9ox7u6wz)** — WATER CHAMPION, Spirit
- Identical effect to Spirit of Serene Fire. Use the same macro pattern.

**Merlin, Kingslayer (rz1bqry41l)** — CRUX CHAMPION, Mage/Warrior
- RecollectionPhase: add a level counter. If counter total is even, draw a card and apply a TurnEffect to the champion's attacks granting +2 POWER.
- Counter add via `AddCounters`, even/odd check, `Draw`, `AddGlobalEffects` for attack power.

---

## Weapons

**Arondight, Azure Blade (29xxoo7dl5)** — WATER REGALIA WEAPON, Warrior/Sword
- Class Bonus On Enter: MZMayChoose loop selecting floating-memory GY cards to banish; for each banished add a refinement counter.
- Continuous +2 POWER per refinement counter: `GetCounters($obj, "refinement") * 2` added in `ObjectCurrentPower`.

**Firetongue (j4lx6xwr42)** — FIRE REGALIA WEAPON, Warrior/Sword
- Class Bonus On Attack: YesNo to banish a fire element card from GY (`ZoneSearch("myGraveyard", cardElements: ["FIRE"])`); if yes, `AddCounters` durability +1.

**Ventus, Staff of Zephyrs (5av43ehjdu)** — WIND REGALIA ITEM, Mage/Staff
- "Whenever you activate a wind element Mage Spell" trigger: add a case for this card's ID in the existing `OnCardActivated` field-listener `switch`, checking `CardClasses($obj->CardID)` for MAGE and element for WIND and type for ACTION; if match, `AddCounters` refinement +1.
- REST ability 1: remove 1 refinement counter, add 1 enlighten counter to champion (`CardActivated` macro, cost: REST + counter check).
- REST ability 2: remove 3 refinement counters, `SuppressAlly` target (`CardActivated` macro, cost: REST + 3-counter check).

**Varuckan Soulknife** — see Hard.md.

---

## Unique Champion-Based Units

**Uther, Illustrious King (5h8asbierp)** — LUXEM UNIQUE ALLY, Warrior/Human
- Intercept, Vigor (schema keywords).
- On Enter: YesNo to REST Uther; if yes, MZChoose any non-champion object on the field → `MZMove` to banishment → `DecisionQueueController::StoreVariable("Uther_BanishedCard", $cardID)` and `StoreVariable("Uther_BanishedFrom", $mzID)`.
- AllyDestroyed: read `GetVariable("Uther_BanishedCard")`; if non-empty, find the card in banishment and `MZMove` it back to the appropriate field zone rested.

**Morgan, Soul Guide (ka5av43ehj)** — NORM UNIQUE ALLY, Cleric/Mage/Human
- Level 1+: prevent all non-combat damage to Morgan — in `OnDealDamage`, if target is Morgan's mzID and there is no combat attacker (`GetVariable("CombatAttacker")` is empty), prevent the damage.
- Level 2+: opponents can't recover — add a check in `RecoverChampion`: if the opponent controls Morgan at Level 2+, return without healing.
- Class Bonus RecollectionPhase: YesNo "Glimpse 1 or Recover 1?" → call `Glimpse($player, 1)` or `RecoverChampion($player, 1)`.

**Parcenet, Royal Maid (xxoo7dl5j4)** — WIND UNIQUE ALLY, Assassin/Human
- Level 2+: Stealth.
- CardActivated REST: `Glimpse(1)` (reveal top card). If that card is WIND element, MZChoose another ally you control and add a TurnEffect giving it stealth until EOT.

---

## Slimes

**Green Slime (zgcxyky280)** — WIND ALLY, Tamer/Beast/Slime
- Pride 3.
- On Enter: `AddCounters($player, mzID, "buff", 2)`.
- Class Bonus AllyDestroyed: read buff counter count at time of death; MZChoose an ally you control, `AddCounters` that count of buff counters to it.

**Cunning Broker (oy34bro89w)** — NORM ALLY, Assassin/Human
- Stealth.
- CardActivated REST + remove 2 prep counters from champion: Draw 1.
