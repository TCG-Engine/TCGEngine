# Raid_ZeroWithNoOtherTuskens
#// COVERAGE: offer=N/A — the card has no target choice at all; both clauses are constant abilities
#//           recomputed on read, so there is no pool to inspect.
#//           decline=N/A — nothing optional.
#//           boundary=Raid_ZeroWithNoOtherTuskens / Raid_OnePerOtherFriendlyTusken (0 vs 2 pins the
#//           per-unit scaling) + Defending_ScalesWithARaidSixUnit (1 vs 6 pins that it reads the Raid
#//           VALUE, not the number of Raid keywords)
#//           control=Defending_ReadsTheDEFENDERSOwnRaid_NotTheChieftains covers the "whose Raid"
#//           question; the aura itself is controller-scoped and proven by
#//           Raid_EnemyTuskenDoesNotCount / Defending_RequiresTheChieftainInPlay.
#//           reqboundary=AcrossTheRequestBoundary
#//
#// HMW_212 The Chieftain, Here Since the Oceans Dried — Unit (Ground) 2/5, cost 3,
#// [Cunning][Heroism], Tusken, UNIQUE.
#// "This unit gains Raid 1 for each other friendly Tusken unit.
#//  While a friendly Tusken unit is defending, it gets +1/+0 for each Raid it has."
#//
#// TWO clauses that feed each other: the first sets HER Raid dynamically, the second turns ANY friendly
#// Tusken's Raid into a DEFENDING bonus — including her own, so her Raid value is read twice over in
#// different roles. Neither clause has a target or a choice; both are recomputed on every read.
#//
#// ⚠ INTERPRETATION, FLAGGED: "for each Raid it has" is read as the Raid VALUE (Raid 2 → +2/+0), not
#// the number of Raid keyword instances (which would be +1 for any Raid at all). HMW is a preview set
#// and carries no entry in card-specific-rulings.md, so this is reasoned, not sourced. The value
#// reading is what makes the clause meaningful — under the instance reading HMW_230 Raiding Party's
#// Raid 6 would contribute +1 — and Defending_ScalesWithARaidSixUnit is the section that separates them.
#//
#// This section: alone, she has no OTHER friendly Tusken, so Raid is 0 and she attacks for her printed 2.

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_212:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P1GROUNDARENAUNIT:0:POWER:2

---

# Raid_OnePerOtherFriendlyTusken
#// The scaling clause: TWO other friendly Tuskens → Raid 2 → she attacks for 2 + 2 = 4.
#// Paired with the section above (0 others → 0 Raid), this is the boundary that pins "1 per unit"
#// rather than a flat grant.
#// ⚠ POWER stays 2 afterwards: Raid is a while-attacking bonus, not a standing stat change.

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_212:1:0 HMW_180:1:0 LAW_082:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:POWER:2

---

# Raid_ExcludesHerselfEvenWithASecondTuskenPresent
#// "for each OTHER friendly Tusken unit" — she is herself a Tusken, so a count that forgets to exclude
#// the source reads one too many. With exactly ONE other Tusken the correct answer is Raid 1 (damage
#// 3); the self-including bug reads Raid 2 (damage 4), which is also exactly what the section above
#// expects — so only this middle value tells the two apart.

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_212:1:0 HMW_180:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3

---

# Raid_NonTuskenFriendlyDoesNotCount
#// The TRAIT is load-bearing, not merely "another friendly unit". SEC_214 Skyhopper Canyon Runner is a
#// blank-text friendly ground unit that is Fringe/Vehicle/Speeder — everything except Tusken.

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [HMW_212:1:0 SEC_214:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2

---

# Raid_EnemyTuskenDoesNotCount
#// "each other FRIENDLY Tusken unit" — controller scope. An enemy Tusken must not raise her Raid.

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_212:1:0
WithP2GroundArena: LOF_209:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2

---

# Defending_ChieftainGetsPlusHerOwnRaid
#// The second clause applied to HER — she is "a friendly Tusken unit" too, with no "other" this time.
#// Two other Tuskens give her Raid 2, so while DEFENDING she deals 2 + 2 = 4 counter-damage.
#// SOR_046 Consular Security Force (3/7) attacks and survives, so the counter number stays readable.

## GIVEN
CommonSetup: yyw/yyw/{myResources:5;theirResources:5}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: [HMW_212:1:0 HMW_180:1:0 LAW_082:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# Defending_ReadsTheDEFENDERSOwnRaid_NotTheChieftains
#// ⚠ WHOSE RAID. "it gets +1/+0 for each Raid IT has" — the DEFENDING unit's own Raid, not the
#// Chieftain's. Here the two differ on purpose: LOF_209 Tusken Tracker has printed Raid 2, while the
#// Chieftain (one other Tusken in play) has Raid 1. The Tracker defends and must deal 2 + 2 = 4, not
#// 2 + 1 = 3.
#// The Chieftain sits at index 0 and is not involved in the combat at all beyond providing the aura.

## GIVEN
CommonSetup: yyw/yyw/{myResources:5;theirResources:5}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: [HMW_212:1:0 LOF_209:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LOF_209
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# Defending_ScalesWithARaidSixUnit
#// ⚠ THE INTERPRETATION DISCRIMINATOR — value vs instance. HMW_230 Raiding Party is a Tusken with
#// printed power 0 and Raid 6. Reading "for each Raid it has" as the Raid VALUE makes it deal
#// 0 + 6 = 6 counter-damage; reading it as "one per Raid keyword" makes it deal 0 + 1 = 1.
#// Every other section in this file uses Raid 1 or 2, where the two readings are close enough to blur;
#// this is the only board where they are unmistakably different.
#// It also proves the bonus is not a flat +1: a 0-power body dealing 6 back can only come from its own
#// Raid value.

## GIVEN
CommonSetup: yyw/yyw/{myResources:5;theirResources:5}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: [HMW_212:1:0 HMW_230:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:HMW_230
P2GROUNDARENAUNIT:0:DAMAGE:6

---

# Defending_NonTuskenWithRaidGetsNothing
#// The aura's TRAIT gate, isolated. SEC_151 Kazuda Xiono also has printed Raid 2 but is Resistance,
#// not Tusken — so it defends for its printed 2 and nothing more. Without this section an
#// implementation that buffs every friendly unit with Raid passes every other defending case here.
#// ⚠ Fixture note: the defender must SURVIVE for its identity to be assertable at index 1. The first
#// draft used LAW_234 Kage Elite (Raid 2 but 2/3), which dies to the 3-power attacker — the section
#// then failed on "no unit at index 1" rather than on the behaviour it was written for. Kazuda is 2/6.

## GIVEN
CommonSetup: yyw/yyw/{myResources:5;theirResources:5}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: [HMW_212:1:0 SEC_151:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_151
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# Defending_RequiresTheChieftainInPlay
#// The aura's SOURCE gate. Identical board to the "whose Raid" section minus the Chieftain: LOF_209
#// defends and deals only its printed 2, because Raid is a while-ATTACKING keyword and nothing is
#// turning it into a defending bonus.
#// This is also what proves the second clause is the Chieftain's ability rather than a change to how
#// Raid works generally.

## GIVEN
CommonSetup: yyw/yyw/{myResources:5;theirResources:5}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: LOF_209:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# Defending_BonusIsCounterDamageOnly_PowerUnchanged
#// The bonus is applied to counter-damage, not written onto the unit as a stat — the same convention
#// the neighbouring "while defending" cards use (LOF_049, SHD_042, ASH_073). Reading POWER back after
#// the attack is the only thing that separates the two, since the damage dealt is identical either way.
#// She has Raid 2 here (two other Tuskens) and still reads POWER 2 afterwards.

## GIVEN
CommonSetup: yyw/yyw/{myResources:5;theirResources:5}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: [HMW_212:1:0 HMW_180:1:0 LAW_082:1:0]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# AcrossTheRequestBoundary
#// THE REQUEST-BOUNDARY CELL. This card raises no decision, so the boundary goes between the two player
#// ACTIONS — playing a second Tusken, then attacking with the Chieftain. Both clauses are constant
#// abilities that must be recomputed from the board in the NEW process; anything cached in an
#// in-memory global when the Tusken entered play is gone by the time the attack resolves, and her Raid
#// silently reads 0.
#// Stormchaser costs 2 and is Aggression against a Cunning base + Cunning/Heroism leader, so it pays
#// +2 off-aspect = 4; the Chieftain then attacks for 2 + Raid 1 = 3.
#// Stormchaser's own When Played finds no Disaster in hand or discard, so it raises no prompt.

## GIVEN
CommonSetup: yyw/yyw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_180
WithP1GroundArena: HMW_212:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:2
P2BASEDMG:3

---

# TwinSuns_DefendingBonusAppliesAgainstAFarSeatAttacker
#// ⚠ THE SEAT-COUNT CELL. Neither clause names an opponent — both are friendly-scoped — so the risk
#// here is not a truncated fan-out but the COMBAT path: the defending bonus has to apply when the
#// attack comes from seat 3 rather than seat 2.
#// The Chieftain has one other friendly Tusken, so Raid 1, and deals 2 + 1 = 3 back to seat 3's
#// attacker. Seat 2 is left empty so the damage can only have come from the far-seat combat.

## GIVEN
CommonSetup: yyw/yyw/{myResources:5}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 3
WithGamePhase: ActionPhase
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP1GroundArena: [HMW_212:1:0 HMW_180:1:0]
WithP3GroundArena: SOR_046:1:0

## WHEN
- P3>AttackGroundArena:0:P1G0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:3
