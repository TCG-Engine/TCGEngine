# HealAndExp
#// SHD_075 Covert Strength (1-cost event) — "Heal 2 damage from a unit and give an Experience token
#// to it." Single friendly target (2-damaged marine) → auto-resolve: damage 0, +1 Experience → 4/4.
#// COVERAGE: offer=HealAndExp proves the pool auto-resolves when exactly one unit is in play, and
#//           UndamagedEnemyUnit_StillGetsExperience proves it spans BOTH sides (an enemy unit is a legal
#//           pick) and is not filtered to damaged units · decline=N/A ("Heal … and give …" is mandatory;
#//           there is no "you may" clause to refuse) · boundary=the heal amount across all three damage
#//           regimes: 4 damage → only 2 healed, 2 remain (SmuggledEvent_ResolvesAndGoesToDiscard) ·
#//           exactly 2 → 0 (HealAndExp) · 0 damage → 0, and the Experience is still granted
#//           (UndamagedEnemyUnit_StillGetsExperience) ·
#//           control=UndamagedEnemyUnit_StillGetsExperience (the Experience token is placed on, and stays
#//           on, an OPPONENT-controlled unit — the effect never re-homes it to the caster) ·
#//           reqboundary=N/A (a one-shot event leaving only a plain Experience upgrade, whose
#//           serialization is covered generically by the shared upgrade/token round-trip cases)

## GIVEN
CommonSetup: bbw/bbw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_075
WithP1GroundArena: SOR_095:1:2

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P1DISCARDCOUNT:1

---

# SmuggledEvent_ResolvesAndGoesToDiscard
#// SHD_075 Covert Strength played via SMUGGLE (from resources) must resolve like any other event: heal 2
#// and give an Experience token, then go to the DISCARD — and the spent slot is replaced from the deck
#// (CR 8.22.g). REGRESSION GUARD: an event smuggled from resources used to fall through the UNIT path in
#// SWUSmuggleResource and be ADDED TO AN ARENA as a bogus "unit" — its effect never resolved and it never
#// reached the discard. Events now delegate to ActivateCard (as upgrades and Plot already did).
## GIVEN
CommonSetup: bbw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:4
WithP1Resources: 1:SHD_075:1,10:SOR_095:1
WithP1Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>SmuggleResource:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:1
P1RESCOUNT:11

---

# UndamagedEnemyUnit_StillGetsExperience
#// SHD_075 Covert Strength — "a unit" is UNQUALIFIED: an ENEMY unit is a legal target, and a target with
#// NO damage is still legal (the heal simply does nothing, but the Experience token is not conditional on
#// it). P1 has a 2-damaged SOR_095 and P2 an undamaged SOR_046 (3/7), so the pick is a real choice; P1
#// picks the enemy: SOR_046 stays at 0 damage and becomes 4/8 with one Experience upgrade, while P1's own
#// damaged unit is left untouched at 2 — the proof the heal went to the chosen unit and only there.

## GIVEN
CommonSetup: bbw/bbw/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_075
WithP1GroundArena: SOR_095:1:2
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:HP:8
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1

---

# SmuggledForeignOwnedEvent_GoesToItsOWNERSDiscard
#// SHD_075 Covert Strength played via SMUGGLE out of a resource that P1 CONTROLS but P2 OWNS
#// (`WithP1ResourceControlled: SHD_075:2` — controller = the arena/zone seat, owner = the `:N` arg).
#// A spent event goes to its OWNER's discard, not the caster's. The normal from-hand play and the
#// smuggled UNIT path both already get this right; only the smuggled EVENT path was wrong.
#// ⚠ RED: the Smuggle event branch delegates to ActivateCard WITHOUT passing an owner, and ActivateCard
#// defaults $owner to the acting player — so the card is filed under the caster. ActivateCard's own
#// discard line is already owner-correct (`SWUAddToDiscard($owner, …)`); it was simply never told.
#// DISCRIMINATES: both discard counts are asserted, so filing it in the wrong pile fails twice.

## GIVEN
CommonSetup: bbw/bbw/{}
P1OnlyActions: true
WithP1ResourceControlled: SHD_075:2
WithP1Resources: 6
WithP1GroundArena: SOR_046:1:2

## WHEN
- P1>SmuggleResource:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1DISCARDCOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
