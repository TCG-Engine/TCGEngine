# Upgraded_GetsPlusOnePlusOne
#// COVERAGE: offer=N/A (no target selection — a self-referential continuous buff, nothing is chosen)
#//           decline=N/A (nothing optional) · boundary=NotUpgraded_NoBuff vs this section (0 vs 1
#//           upgrades IS the threshold, and it is a pair)
#//           control=N/A ("this unit" is self-referential; no owner-scoped zone, no controller wording)
#//           reqboundary=N/A (recomputed continuously in ObjectCurrentPower/HP — nothing is written
#//           before a decision and read behind it, and no phase-scoped state is carried between actions)
#//           modes=2P only (no player reference, no friendly/enemy wording — "this unit" is self)
#//
#// HMW_073 Peppi Bow, Shaak Herder — 2-cost 2/3 Gungan (Vigilance/Heroism), unique.
#//   "Restore 1 (When this unit attacks, heal 1 damage from your base.)
#//    While this unit is upgraded, she gets +1/+1."
#// ⚠ PREVIEW SET — no official rulings exist for HMW. The upgraded clause is word-for-word SHD_056
#// Follower of The Way ("While this unit is upgraded, it gets +1/+1"), which is already implemented, so
#// this follows that precedent rather than a ruling. Restore 1 is keyword-only and already in
#// $Restore_Cards, so it needs no code — one section below verifies it on this card anyway.
#//
#// THE POSITIVE. Base 2/3 + SOR_120 Academy Training (+2/+2) + her own +1/+1 = 5/6.
#// ⚠ She is UNIQUE, so the un-upgraded control cannot be a second copy on the same board (the way
#// SHD_056's single section does it) — it is its own section below.

## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_073:1:0
WithP1GroundArenaUpgrade: 0:SOR_120

## WHEN
#// Drain, not Pass: Pass ENDS the action phase and (with no seeded deck) puts deck-out damage on the
#// base, which moves numbers a board-reading section has no reason to touch.
- P1>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_073
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:6

---

# NotUpgraded_NoBuff
#// THE NEGATIVE that proves the gate is load-bearing. Identical board with no upgrade: printed 2/3.
#// Without this, the positive above passes for a card that simply had +1/+1 printed on it.
## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_073:1:0
## WHEN
- P1>Drain
## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:3

---

# ShieldTokenCountsAsAnUpgrade
#// VALUE-CLASS VARIANT, and the one most likely to be missed: "upgraded" is about SUBCARDS, not about
#// stat-bearing upgrades. A Shield token (SOR_T02) contributes NO power or HP of its own, so it isolates
#// the self-buff exactly — 2/3 + her own +1/+1 = 3/4, with nothing else that could account for it.
## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_073:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
## WHEN
- P1>Drain
## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:4

---

# ExperienceTokenCountsAsAnUpgrade
#// The other token class, and it stacks: Experience (SOR_T01) is itself +1/+1, so 2/3 + 1/+1 + her own
#// +1/+1 = 4/5. Reading 3/4 here would mean the token was counted for the CONDITION but not for stats;
#// reading 3/4 in the Shield section above would mean the reverse. The pair separates them.
## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_073:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
## WHEN
- P1>Drain
## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:5

---

# UpgradeDefeated_BuffRECOMPUTESAway
#// THE DURATION/END CELL. A "while X" aura must RECOMPUTE, not stamp: P1 plays SOR_251 Confiscate on
#// Peppi's own upgrade and she must fall all the way back to printed 2/3. A buff written once onto the
#// unit passes every section above and only fails here.
#// ⚠ Confiscate is neutral-aspect and cost 1, so it plays under any leader without a penalty.
## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [SOR_251]
WithP1GroundArena: HMW_073:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:3

---

# Blanked_NoBuffEVENTHOUGHUpgraded
#// THE SHARPEST NEGATIVE, and the reason it is sharp: SHD_072 Imprisoned is +0/+2000 in neither
#// direction — it is +0/+0 and it BLANKS its host ("attached unit loses its current abilities and can't
#// gain abilities"). So the very upgrade that satisfies "while this unit is upgraded" also removes the
#// ability that would pay out. Correct answer is printed 2/3.
#// A `!$lost` gate that was forgotten reads 3/4 here and is invisible in every other section, because no
#// other section has a blanked Peppi.
## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_073:1:0
WithP1GroundArenaUpgrade: 0:SHD_072
## WHEN
- P1>Drain
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:3

---

# TheHPHalfIsREALInCombat_NotJustDisplayed
#// Combat lethality reads ObjectCurrentHP, so the +1 HP has to keep her alive — asserting `:HP:` alone
#// proves the number is computed, not that anything consumes it.
#// Peppi + SOR_069 Resilient (+0/+3) = 3/7, seeded with 3 damage. SOR_046 (3 power) attacks: 3+3 = 6
#// damage against 7 HP, so she survives on 1 remaining. WITHOUT her own +1 HP she would be 2/6 and 6
#// damage is exactly lethal — the section is chosen so one point of HP is the whole difference.
#// Her counter-damage of 3 also pins the POWER half in combat (2 without the buff).
#// ⚠ P2 must actually act, so no P1OnlyActions here.
## GIVEN
CommonSetup: bbw/bbw
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: HMW_073:1:3
WithP1GroundArenaUpgrade: 0:SOR_069
WithP2GroundArena: SOR_046:1:0
## WHEN
- P2>AttackGroundArena:0:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:6
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# Restore1_HealsYourBaseOnAttack
#// The card's OTHER clause. Restore 1 is keyword-only and auto-wired from $Restore_Cards, so this is a
#// verification section rather than new behaviour — but the card has two clauses and shipping one
#// untested is exactly how a keyword-plus-rider card goes half-covered.
#// P1's base starts on 3 damage; Peppi attacks the enemy base and Restore heals 1, leaving 2.
## GIVEN
CommonSetup: bbw/bbw/{myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_073:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:2
P2BASEDMG:2
