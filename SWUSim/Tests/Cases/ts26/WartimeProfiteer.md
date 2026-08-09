# WhenDefeatedOppReadyResource
#// TS26_76 Wartime Profiteer (Ground, 3/3) — When Defeated: each opponent may ready a resource.
#// Profiteer (pre-damaged to 1 HP) attacks LAW_124 and dies to the counter; P2 (1 exhausted resource)
#// readies it → P2 ready resources 2 → 3.
## GIVEN
CommonSetup: yyw/rrk
WithActivePlayer: 1
WithP1GroundArena: TS26_76:1:2
WithP2GroundArena: LAW_124:1:0
WithP2Resources: 2:SOR_046:1,1:SOR_046:0
## WHEN
- P1>AttackGroundArena:0:0
- P2>AnswerDecision:YES
## EXPECT
P2RESAVAILABLE:3

---

# TheOpponentMayDecline
#// TS26_76 Wartime Profiteer — "each opponent MAY ready a resource". P2 answers no: their exhausted
#// resource stays exhausted, so 2 of their 3 remain ready.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: TS26_76:1:2
WithP2GroundArena: LAW_124:1:0
WithP2Resources: 2:SOR_046:1,1:SOR_046:0

## WHEN
- P1>AttackGroundArena:0:0
- P2>AnswerDecision:NO

## EXPECT
P2RESAVAILABLE:2
P2RESCOUNT:3

---

# NoExhaustedResourceMeansNothingToReady
#// TS26_76 Wartime Profiteer — with all of P2's resources already ready there is nothing the ability can
#// do, so it passes silently. The Profiteer still dies to LAW_124's counter-damage.

## GIVEN
CommonSetup: yyw/rrk
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: TS26_76:1:2
WithP2GroundArena: LAW_124:1:0
WithP2Resources: 2

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2RESAVAILABLE:2
P1GROUNDARENACOUNT:0
