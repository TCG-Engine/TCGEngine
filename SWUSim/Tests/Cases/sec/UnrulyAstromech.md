# WhenDefeated_ExhaustEnemy
#// SEC_221 Unruly Astromech (Ground, 3/2) — Hidden + When Defeated: exhaust an enemy unit. SEC_221
#//   attacks SOR_046 (3/7) and dies to the counter; on defeat the only enemy (SOR_046) is exhausted.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_221:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# WhenDefeated_ExhaustFriendly_AfterControlChange
#// SEC_221 Unruly Astromech — When Defeated: exhaust an enemy unit. The ability's controller is whoever
#//   controls the unit when it dies. P2 uses JTL_043 No Glory, Only Results to take control of Unruly
#//   Astromech and defeat it, so the When Defeated resolves for P2 — its "enemy" units are now P1's. P2
#//   exhausts one of P1's remaining units (SOR_164), proving control transferred to P2.

## GIVEN
CommonSetup: yyk/bbk
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 6
WithP1GroundArena: SEC_221:1:0
WithP1GroundArena: SOR_164:1:0
WithP1GroundArena: SOR_095:1:0
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_164
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SOR_095
