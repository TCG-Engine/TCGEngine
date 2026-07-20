# ReuseWhenDefeated
#// JTL_169 Shadow Caster — When a friendly unit is defeated: you may use all of its
#// "When Defeated" abilities again.
#// JTL_087 dies attacking SOR_044 → its When Defeated creates a TIE (use #1); Shadow Caster
#// lets P1 use it again → a 2nd TIE (use #2). Arena = Shadow Caster + 2 TIEs = 3.

## GIVEN
CommonSetup: gbk/bbk/{
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_087:1:1
WithP1SpaceArena: JTL_169:1:0
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:3

---

# DoesNotReuseEnemyWhenDefeated
#// JTL_169 Shadow Caster reuses only FRIENDLY When Defeated abilities. P1's Daring Raid (TWI_170) defeats
#// P2's Rhokai Gunship (SHD_164, "When Defeated: deal 1 to a unit or base"). That When Defeated belongs to
#// P2 (its controller), so Shadow Caster does not offer P1 a reuse — Rhokai's ability fires exactly once
#// (P2 points it at P1's base → 1 damage), and P1 gets no reuse prompt.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
SkipPreGame: true
WithActivePlayer: 1
WithP1SpaceArena: JTL_169:1:0
WithP1Hand: TWI_170
WithP2SpaceArena: SHD_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P2>Drain
- P2>AnswerDecision:theirBase-0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENACOUNT:1
P1BASEDMG:1
P1NODECISION

---

# ReuseClonedWhenDefeated
#// JTL_169 Shadow Caster reuses a When Defeated the dying unit gained by being a COPY. P1's Clone (TWI_116)
#// enters as a copy of OOM-Series Officer (TWI_131, "When Defeated: deal 2 to a base"). P2's Daring Raid
#// (TWI_170) defeats the Clone; its copied When Defeated deals 2 to P2's base, then Shadow Caster (friendly)
#// lets P1 use it again for 2 more → 4 total. The Clone's controller (P1) is non-active, so the trigger is
#// drained with P1>Drain.

## GIVEN
CommonSetup: ggk/rrk/{myResources:8;theirResources:3}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: TWI_116
WithP1GroundArena: TWI_131:1:0
WithP1SpaceArena: JTL_169:1:0
WithP2Hand: TWI_170

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1
- P1>Drain
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:1

---

# ReuseWhenDefeatedGainedFromEvent
#// JTL_169 Shadow Caster reuses a When Defeated the dying unit GAINED FROM AN EVENT (not innate). P1 plays
#// In Defense of Kamino (TWI_129), granting every friendly Republic unit "When Defeated: create a Clone
#// Trooper" this phase. P2's Rivals Fall (SHD_079) defeats P1's Padawan Starfighter (TWI_058, Republic):
#// its granted When Defeated creates Clone Trooper #1, then Shadow Caster (friendly) lets P1 use it again
#// for a 2nd Clone Trooper. Was a real engine gap — Shadow Caster only ever offered reuse for INNATE When
#// Defeateds, silently ignoring event-/upgrade-/field-granted ones (the same bug class fixed for Thrawn
#// (JTL_002) in session 82 and Chimaera (JTL_039) in session 83). Padawan's controller (P1) is non-active,
#// so the defeat trigger is drained with P1>Drain.

## GIVEN
CommonSetup: ggk/rrk/{myResources:10;theirResources:10}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: TWI_129
WithP1SpaceArena: TWI_058:1:0
WithP1SpaceArena: JTL_169:1:0
WithP2Hand: SHD_079

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Drain
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_169
P1GROUNDARENACOUNT:2

---

# NotTriggeredByChimaeraActivation
#// JTL_169 Shadow Caster triggers on a "friendly unit is DEFEATED", NOT on a friendly When Defeated being
#// USED without a defeat. P1 plays Chimaera (JTL_039), which uses Wartime Trade Official's (TWI_032)
#// "When Defeated: create a Battle Droid" on the LIVING Wartime Trade Official — it is not defeated. Exactly
#// one Battle Droid (TWI_T01) is created; Shadow Caster does NOT offer a reuse (no defeat occurred). Ground
#// arena ends with Wartime + Wampa + 1 Battle Droid = 3 (a 2nd droid would mean Shadow Caster wrongly fired).

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_039
WithP1GroundArena: TWI_032:1:0
WithP1GroundArena: SOR_164:1:0
WithP1SpaceArena: JTL_169:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:3
P1SPACEARENACOUNT:2
P1NODECISION

---

# AfterThrawnAbility_BothReuses
#// JTL_169 Shadow Caster + JTL_002 Grand Admiral Thrawn (leader) COMPOSE — a single friendly defeat can be
#// reused by BOTH. P1 controls Thrawn (undeployed, ready) and Shadow Caster; plays In Defense of Kamino
#// (grants Padawan Starfighter a "When Defeated: create a Clone Trooper" this phase). P2's Rivals Fall
#// defeats the Padawan → its granted When Defeated makes Clone Trooper #1. P1 then accepts BOTH reuse
#// offers (Thrawn exhausts to use it again; Shadow Caster uses it again) → Clone Troopers #2 and #3. End =
#// 3 Clone Troopers. (The reference engine tests both offer orders via explicit prompt buttons; SWUSim
#// auto-sequences the two YES/NO offers, and the end state is 3 regardless of order.)

## GIVEN
CommonSetup: ggk/rrk/{
  myLeader:JTL_002;
  myResources:10;
  theirResources:10
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: TWI_129
WithP1SpaceArena: TWI_058:1:0
WithP1SpaceArena: JTL_169:1:0
WithP2Hand: SHD_079

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Drain
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_169
P1GROUNDARENACOUNT:3
P1LEADER:EXHAUSTED

---

# AfterThrawnAbility_OneReuseDeclined
#// JTL_169 Shadow Caster + JTL_002 Thrawn — the two reuse offers are INDEPENDENT. Same setup as
#// AfterThrawnAbility_BothReuses, but P1 accepts one reuse offer and declines the other → exactly one extra
#// Clone Trooper. End = 2 Clone Troopers (the original + one reuse), regardless of which offer was declined.

## GIVEN
CommonSetup: ggk/rrk/{
  myLeader:JTL_002;
  myResources:10;
  theirResources:10
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: TWI_129
WithP1SpaceArena: TWI_058:1:0
WithP1SpaceArena: JTL_169:1:0
WithP2Hand: SHD_079

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Drain
- P1>AnswerDecision:YES
- P1>AnswerDecision:NO

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:2

---

# ReuseMultipleWhenDefeateds
#// JTL_169 Shadow Caster — "use ALL of its When Defeated abilities again." AT-TE Vanguard (TWI_247) has an
#// INNATE "When Defeated: create 2 Clone Trooper tokens" AND, via In Defense of Kamino (TWI_129), a GRANTED
#// "When Defeated: create a Clone Trooper" this phase. P2's Rivals Fall defeats it → both fire (2 + 1 = 3
#// clones), then Shadow Caster lets P1 reuse EACH (innate again = +2, granted again = +1) → 6 Clone Troopers
#// total. End-state check (order-agnostic): every reuse offer accepted. Clones (TWI_T02) are ground tokens;
#// AT-TE was the only P1 ground unit (now defeated), so P1 ground arena = 6 clones, space = Shadow Caster.

## GIVEN
CommonSetup: ggk/rrk/{myResources:12;theirResources:12}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: TWI_129
WithP1GroundArena: TWI_247:1:0
WithP1SpaceArena: JTL_169:1:0
WithP2Hand: SHD_079

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Drain
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_169
P1GROUNDARENACOUNT:6
