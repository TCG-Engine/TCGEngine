# FTC Cards — Implementable with Current Engine
For cards requiring new engine development, see Hard.md.

---

## Ally Champions (Inner Lineage)

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
