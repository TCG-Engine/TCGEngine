# WhenDefeated_Return
#// ASH_038 Purrgil Ultra — the same ability also triggers When Defeated. Purrgil (pre-damaged to 1 HP)
#// attacks SOR_237 (2/3) and dies to the counter; its When Defeated returns SEC_135 to hand (the deal-damage
#// rider then fizzles since no unit remains to target).
## GIVEN
CommonSetup: gyk/gyk
WithP1SpaceArena: ASH_038:1:9
WithP2SpaceArena: SOR_237:1:0
WithP1GroundArena: SEC_135:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0

---

# WhenPlayed_Decline
#// ASH_038 Purrgil Ultra — declining the optional return leaves the board untouched (no return, no damage).
## GIVEN
CommonSetup: gyk/gyk/{myResources:8;handCardIds:ASH_038}
WithP1GroundArena: SEC_135:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1

---

# WhenPlayed_ReturnAndDamage
#// ASH_038 Purrgil Ultra (Space, 6/10, cost 8) — When Played: you may return another friendly non-leader
#// unit to its owner's hand; if you do, deal damage to a unit equal to the returned unit's cost. P1 returns
#// SEC_135 (cost 3) and deals 3 to SEC_080 (3/3), defeating it.
## GIVEN
CommonSetup: gyk/gyk/{myResources:8;handCardIds:ASH_038}
WithP1GroundArena: SEC_135:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# WhenPlayed_ReturnDamageFriendly
#// ASH_038 Purrgil Ultra — the deal-damage rider may target ANY unit, including a friendly one. P1 returns
#// SEC_135 (cost 3) and deals 3 damage to its own SEC_080 (3/3), defeating it. Both friendly ground units
#// leave play (one returned, one defeated).
## GIVEN
CommonSetup: gyk/gyk/{myResources:8;handCardIds:ASH_038}
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_135:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1

---

# WhenDefeated_ReturnDamageEnemy
#// ASH_038 Purrgil Ultra — the When Defeated trigger. Purrgil (pre-damaged to 9 = 1 HP) attacks SOR_237 (2/3),
#// defeating it and dying to the 2 counter. When Defeated: return friendly SEC_135 (cost 3) and deal 3 to the
#// surviving enemy SEC_080 (3/3), defeating it.
## GIVEN
CommonSetup: gyk/gyk
WithP1SpaceArena: ASH_038:1:9
WithP1GroundArena: SEC_135:1:0
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# WhenDefeated_ReturnDamageFriendly
#// ASH_038 Purrgil Ultra — the When Defeated deal-damage rider may target a friendly unit. Purrgil dies to
#// the counter; it returns friendly SEC_135 (cost 3) and deals 3 to its own SEC_080 (3/3), defeating it.
## GIVEN
CommonSetup: gyk/gyk
WithP1SpaceArena: ASH_038:1:9
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SEC_135:1:0
WithP2SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0

---

# WhenDefeated_Pass
#// ASH_038 Purrgil Ultra — the When Defeated ability is optional. Declining the return leaves the board
#// untouched: SEC_135 stays in play and no damage is dealt.
## GIVEN
CommonSetup: gyk/gyk
WithP1SpaceArena: ASH_038:1:9
WithP1GroundArena: SEC_135:1:0
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:-
## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# Offer_AnotherFriendlyNONLEADERUnit_ExcludesSelfLeaderAndEnemies
#// ASH_038 — "return ANOTHER FRIENDLY NON-LEADER unit". Three restrictions stack, and only a board that
#// violates each one separately can tell them apart. The pool must contain exactly the two ordinary
#// friendly units:
#//   • Purrgil itself       — excluded by "another"
#//   • the deployed leader  — excluded by "non-leader" (it is a friendly unit in the arena)
#//   • the enemy unit       — excluded by "friendly"
#// Asserted while pending; answering would exercise one target and prove nothing about the other three.

## GIVEN
CommonSetup: gyk/rrk/{myResources:12; handCardIds:ASH_038; myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_225:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_028:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0
