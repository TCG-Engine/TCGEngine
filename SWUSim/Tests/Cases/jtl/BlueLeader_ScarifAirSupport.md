# Decline_StaysInSpace
#// JTL_096 Blue Leader — the "may pay 2" is optional. Declining (AnswerDecision:NO) leaves Blue Leader
#// in the space arena as a plain 3/3 with no Experience.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_096}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:JTL_096
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:POWER:3

---

# MoveAmbush_LukeHealsAttacker
#// JTL_096 Blue Leader + ASH_005 Luke Skywalker (undeployed leader) — full game-2151 chain.
#// P2 plays Blue Leader; Ambush (target = P1 space Chimaera at collection) + When Played (pay 2 → move to
#// ground + 2 XP) both fire. P2 resolves When-Played FIRST → Blue Leader becomes a 5/5 in the ground arena.
#// Ambush re-resolves the moved unit; its only ground target is P1's Thrawn leader-unit (JTL_002 4/7 @ 2
#// dmg), so it auto-fires (single target). Combat: Blue Leader deals 5 → Thrawn (2+5 ≥ 7) defeated; Thrawn
#// deals 4 back → Blue Leader at 4 damage. Then Luke's "when a friendly unit's attack ends" fires: P2
#// exhausts Luke to heal 1 from Blue Leader, leaving it at 3 damage (2 HP remaining).

## GIVEN
CommonSetup: ngw/ngw/{
  myLeader:JTL_002:true:true:true:2:0;
  myBase:SEC_026;
  myBaseDamage:14;
  theirLeader:ASH_005:true:false:false:0;
  theirBase:JTL_024;
  theirBaseDamage:8;
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1Resources: 7:SOR_095:1
WithP2Resources: 5:SOR_095:1,2:SOR_095:0
WithP1SpaceArena: [JTL_039:0:0]
WithP2GroundArena: [ASH_105:0:0]
WithP2Hand: [SEC_051 JTL_096]

## WHEN
- P2>PlayHand:1
- P2>ResolveTrigger:WhenPlayed
- P2>AnswerDecision:YES
- P2>AnswerDecision:YES
- P2>AnswerDecision:YES

## EXPECT
#// Blue Leader moved to P2 ground (idx 1, after Bo-Katan) as a 5/5 and survived the attack …
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:CARDID:JTL_096
P2GROUNDARENAUNIT:1:POWER:5
P2GROUNDARENAUNIT:1:HP:5
#// … Thrawn defeated by the Ambush attack …
P1GROUNDARENACOUNT:0
#// … and Luke exhausted to heal 1 (4 counter damage − 1 heal = 3).
P2GROUNDARENAUNIT:1:DAMAGE:3
P2LEADER:EXHAUSTED

---

# MoveToGroundFirst_ThenAmbush
#// JTL_096 Blue Leader — Ambush + "When Played: pay 2 → move to the ground arena + 2 Experience" BOTH
#// trigger on play. With an enemy SPACE unit present at collection, Ambush has a target so TWO entry
#// triggers fire and P2 orders them. P2 resolves the When-Played FIRST (Blue Leader → ground as a 5/5),
#// then Ambush must re-resolve the now-moved unit and attack the enemy GROUND unit (JTL_002, a 4/7 leader
#// unit at 2 damage), defeating it.
#//
#// Regression for the stale-mzID fizzle (repro of game 2151): before the fix the Ambush entry-trigger held
#// Blue Leader's original (space) mzID; the When-Played move-to-ground marked that slot removed, so
#// GetZoneObject returned null and Ambush silently fizzled (no attack, enemy leader survived). The fix
#// carries the unit's UID on the Ambush trigger and re-resolves it at dispatch.

## GIVEN
CommonSetup: ngw/ngw/{
  myLeader:JTL_002:true:true:true:2:0;
  myBase:SEC_026;
  myBaseDamage:14;
  theirLeader:ASH_005:true:false:false:0;
  theirBase:JTL_024;
  theirBaseDamage:8;
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1Resources: 7:SOR_095:1
WithP2Resources: 5:SOR_095:1,2:SOR_095:0
WithP1SpaceArena: [JTL_039:0:0]
WithP2GroundArena: [ASH_105:0:0]
WithP2Hand: [SEC_051 JTL_096]

## WHEN
- P2>PlayHand:1
- P2>ResolveTrigger:WhenPlayed
- P2>AnswerDecision:YES
- P2>AnswerDecision:YES
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
#// When-Played moved Blue Leader to P2's ground (joining Bo-Katan at idx 0) as a 5/5 (3/3 + 2 Experience) …
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:CARDID:JTL_096
P2GROUNDARENAUNIT:1:UPGRADECOUNT:2
P2GROUNDARENAUNIT:1:POWER:5
P2GROUNDARENAUNIT:1:HP:5
#// … then Ambush re-resolved the moved unit and defeated the enemy ground leader (JTL_002 4/7 @ 2 dmg).
P1GROUNDARENACOUNT:0

---

# Pay2_MoveToGround_2Exp
#// JTL_096 Blue Leader — Ambush + "When Played: You may pay 2 resources. If you do, move this unit to
#// the ground arena and give 2 Experience tokens to it." Played into an empty enemy board (Ambush has
#// no target → only the WhenPlayed fires); P1 pays 2 and Blue Leader moves to the ground arena as a 5/5
#// (3/3 base + 2 Experience).

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_096}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_096
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5

---

# GroundAfterMove_HitByGroundAOE
#// JTL_096 Blue Leader — once it pays 2 to move to the GROUND arena it is a ground unit for all purposes.
#// A "deal to each other GROUND unit" effect hits it. P1 plays Blue Leader into an empty enemy board
#// (Ambush fizzles) and pays 2 → it becomes a 5/5 in the ground arena. P2 then plays SHD_158 Wild Rancor
#// ("When Played: Deal 2 damage to each other ground unit"), which damages Blue Leader for 2.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;theirResources:6}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: JTL_096
WithP2Hand: SHD_158

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_096
P1GROUNDARENAUNIT:0:DAMAGE:2
P1SPACEARENACOUNT:0

---

# GroundAfterMove_NotSpaceTargetable
#// JTL_096 Blue Leader — after moving to the ground arena it is no longer a SPACE unit, so a
#// "choose a space unit" effect can't reach it. P1 plays Blue Leader (Ambush fizzles — no enemy unit)
#// and pays 2 → it becomes a 5/5 GROUND unit; P1 also has SOR_209 in the SPACE arena. P2 plays JTL_176
#// Shoot Down ("Deal 3 damage to a SPACE unit"): the only legal target is SOR_209 (Blue Leader is now
#// ground and unreachable), so it auto-resolves onto SOR_209 (3 damage) and Blue Leader is untouched.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;theirResources:4}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: JTL_096
WithP1SpaceArena: SOR_209:1:0
WithP2Hand: JTL_176

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_096
P1GROUNDARENAUNIT:0:DAMAGE:0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_209
P1SPACEARENAUNIT:0:DAMAGE:3

---

# GroundAfterMove_NotCountedForControlSpaceCondition
#// JTL_096 Blue Leader — after moving to the ground arena it does NOT count for "if you control another
#// space unit" conditions. P1 plays Blue Leader and pays 2 → 5/5 ground unit. P1 then plays JTL_217
#// Death Space Skirmisher ("When Played: If you control another space unit, you may exhaust a unit"):
#// Death Space Skirmisher is now P1's ONLY space unit (Blue Leader is ground), so the condition is
#// false and no exhaust is offered at all — no decision is pending.

## GIVEN
CommonSetup: ygw/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_096
WithP1Hand: JTL_217

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_096
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_217
P1NODECISION

---

# AmbushFirst_ThenMoveToGround
#// JTL_096 Blue Leader — the two entry triggers (Ambush + "When Played: pay 2 → move to ground") may be
#// resolved in EITHER order. Here P1 resolves AMBUSH FIRST against a weak 2/2 (JTL_160 Supporting Eta-2):
#// Blue Leader (3/3) deals 3 (defeating it) and takes 2 back. THEN the When-Played resolves — P1 pays 2
#// and Blue Leader moves to the ground arena as a 5/5 (3/3 + 2 Experience) still carrying its 2 combat
#// damage → a 5-power unit with 3 remaining HP. (With a single enemy unit the Ambush auto-targets, so no
#// target answer is needed — the two YES answers are the may-attack opt-in and the pay-2 opt-in.)

## GIVEN
CommonSetup: ggw/yrk/{myResources:8;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_096
WithP2SpaceArena: JTL_160:1:0

## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:Ambush
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_096
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# AmbushFirst_LethalCounter_WhenPlayedFizzles
#// JTL_096 Blue Leader — resolving Ambush FIRST into a unit whose counter DEFEATS Blue Leader means the
#// When-Played "move to the ground arena" has nothing left to move, so it fizzles harmlessly. Blue Leader
#// (3/3) ambushes JTL_249 Millennium Falcon (3/4): it deals 3 (Falcon survives at 3 damage) and takes 3
#// back — its own 3 HP → defeated to the discard. The When-Played then dispatches, finds Blue Leader gone,
#// and does nothing (no pay prompt, no move, no error).

## GIVEN
CommonSetup: ggw/yrk/{myResources:8;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_096
WithP2SpaceArena: JTL_249:1:0

## WHEN
- P1>PlayHand:0
- P1>ResolveTrigger:Ambush
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_249
P2SPACEARENAUNIT:0:DAMAGE:3
P1NODECISION
