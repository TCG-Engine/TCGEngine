# WhenDefeatedExhaustResource
#// ASH_216 Mandalorian Scout (Ground, 3/3, cost 2) — When Defeated: exhaust a ready friendly resource. The
#// Scout attacks SOR_046 (3/7) and dies to the counter; its WhenDefeated exhausts one of P1's 3 ready
#// resources (3 → 2 ready).
## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
WithP1GroundArena: ASH_216:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:2

---

# NGOR_NewControllerExhaustsResource
#// ASH_216 Mandalorian Scout — the When Defeated "exhaust a ready friendly resource" resolves for whoever
#// controls the Scout at defeat. P2 uses No Glory, Only Results (JTL_043) to take control of P1's Scout and
#// defeat it, so the exhaust hits P2's OWN resource (10 → 5 after paying the cost, then 4) while P1's 5
#// ready resources are untouched.
## GIVEN
CommonSetup: yyk/bbk/{myResources:5}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 10
WithP2Hand: JTL_043
WithP1GroundArena: ASH_216:1:0
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:5
P2RESAVAILABLE:4
