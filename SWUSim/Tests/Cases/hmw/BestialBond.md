# CreatureHost_CreatesABeastToken
#// HMW_038 Bestial Bond — Upgrade, cost 3, +2/+2, [Command][Vigilance], trait Innate, non-unique.
#// Text: "When Played: If attached unit is a Creature or a Force unit, create a Beast token."
#// COVERAGE: offer=HostPoolIncludesEnemyUnits — the card prints NO attach restriction, so under CR 2.e
#//           the host pool is every unit on the table; asserted as a pending pool with a friendly and
#//           an enemy host both present ·
#//           decline=N/A — nothing optional. "If attached unit is…" is a CONDITION, not a choice, and
#//           the token creation is mandatory. The false branch is
#//           NeitherCreatureNorForce_NoBeastButStatsStillApply ·
#//           boundary=N/A (no numeric threshold anywhere in the card) ·
#//           control=EnemyCreatureHost_TokenGoesToTheUpgradesController — the host and the token's
#//           owner are DIFFERENT players whenever the upgrade is played on an enemy unit ·
#//           reqboundary=RequestBoundary_HostChoiceSurvivesTheBoundary ·
#//           modes=2P only (no player reference, no friendly/enemy wording — "attached unit" and
#//           "create a Beast token" both resolve without naming a seat).
#// ⚠ PREVIEW SET: HMW is absent from card-specific-rulings.md. The reading of "create a Beast token" as
#//   belonging to the player who PLAYED the upgrade (not the host's controller) matches HMW_265 Twi'lek
#//   Kalikori's "your deck", settled the same way earlier today.
#//
#// LOF_168 is a vanilla 8/5 Creature. The upgrade attaches (auto — it is the only unit on the table),
#// takes it to 10/7, and the Beast token HMW_T03 (a 3/3 ground Creature) is created EXHAUSTED, as every
#// effect-created token is unless the text says otherwise.

## GIVEN
CommonSetup: gbw/gbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_038
WithP1GroundArena: LOF_168:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:LOF_168
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_038
P1GROUNDARENAUNIT:0:POWER:10
P1GROUNDARENAUNIT:0:HP:7
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3
P1GROUNDARENAUNIT:1:EXHAUSTED
P1NODECISION

---

# ForceHost_CreatesABeastToken
#// HMW_038 — the OTHER half of the "Creature OR Force" gate, on its own. SOR_192 Ezra Bridger is a 3/4
#// Force unit and NOT a Creature, so only the Force branch can fire here.

## GIVEN
CommonSetup: gbw/gbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_038
WithP1GroundArena: SOR_192:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_192
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:6
P1GROUNDARENAUNIT:1:CARDID:HMW_T03

---

# NeitherCreatureNorForce_NoBeastButStatsStillApply
#// HMW_038 — the gate's NEGATIVE. SOR_095 Battlefield Marine is a Rebel Trooper: neither branch fires,
#// so no token is created. The printed +2/+2 is NOT an ability and still applies (3/3 → 5/5), which is
#// what separates "the condition was evaluated" from "the upgrade did nothing at all".

## GIVEN
CommonSetup: gbw/gbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_038
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1NODECISION

---

# CreatureAndForceHost_StillOnlyOneBeast
#// HMW_038 — "a Creature OR a Force unit" is one condition with two ways to be true, not two triggers.
#// SOR_056 satisfies BOTH (traits Creature, Force) and must still produce exactly ONE Beast. An
#// implementation written as two independent ifs, each creating a token, passes every other section in
#// this file and doubles up only here.

## GIVEN
CommonSetup: gbw/gbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_038
WithP1GroundArena: SOR_056:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_056
P1GROUNDARENAUNIT:1:CARDID:HMW_T03

---

# BeastTokenIsItselfACreature_CreatesASecondBeast
#// HMW_038 — a Beast token is a Creature, so bonding to one makes another. Also proves the host pool
#// and the +2/+2 both work on a TOKEN unit rather than only on real cards.

## GIVEN
CommonSetup: gbw/gbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_038
WithP1GroundArena: HMW_T03:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1GROUNDARENAUNIT:1:POWER:3

---

# ForceTraitRemoved_NoBeast
#// HMW_038 — the trait must be read from the LIVE OBJECT, not from the printed card data. SEC_054
#// Exiled from the Force reads "Attached unit loses the Force trait", so Ezra wearing it is no longer a
#// Force unit and no Beast is created. A bare-CardID HasTrait lookup would still see "Force" in the
#// dictionary and create one — this is the only section that can tell the two apart.

## GIVEN
CommonSetup: gbw/gbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_038
WithP1GroundArena: SOR_192:1:0
WithP1GroundArenaUpgrade: 0:SEC_054

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_192
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1NODECISION

---

# EnemyCreatureHost_TokenGoesToTheUpgradesController
#// HMW_038 — host versus beneficiary. The card prints no attach restriction, so it may legally be
#// played on an ENEMY unit (CR 2.e); the enemy Creature then gets the +2/+2, but "create a Beast token"
#// belongs to the player who PLAYED the upgrade. The Beast lands in P1's arena while P2's board holds
#// only their own (now buffed) Creature. Both boards are asserted.

## GIVEN
CommonSetup: gbw/gbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_038
WithP2GroundArena: LOF_168:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LOF_168
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:10
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_T03

---

# HostPoolIncludesEnemyUnits
#// HMW_038 — the OFFER cell. No printed attach restriction means the pool is every unit on the table,
#// friendly and enemy alike. Three units are seeded so the host choice really prompts (with fewer it
#// auto-attaches and there is no pool to inspect), and the pool is asserted while pending.

## GIVEN
CommonSetup: gbw/gbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_038
WithP1GroundArena: LOF_168:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_192:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0

---

# RequestBoundary_HostChoiceSurvivesTheBoundary
#// HMW_038 — the request-boundary cell. With two legal hosts the attach really prompts, so in
#// production the answer arrives in a fresh process with the upgrade mid-play. The chosen host must
#// still be the one that gets the upgrade, and the When Played must still see it.

## GIVEN
CommonSetup: gbw/gbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: HMW_038
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: LOF_168:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:LOF_168
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:POWER:10
P1GROUNDARENAUNIT:2:CARDID:HMW_T03
