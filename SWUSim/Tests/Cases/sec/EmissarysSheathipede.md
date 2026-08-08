# WhenDefeated_OppReadyResource
#// SEC_215 Emissary's Sheathipede (Space, 2/4) — When Defeated: each opponent may ready a resource.
#//   SEC_215 (pre-damaged to 1 HP) attacks SOR_237 and dies to the counter; P2 (1 exhausted resource)
#//   chooses to ready it → P2 ready resources 2 → 3.

## GIVEN
CommonSetup: yyw/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_215:1:3
WithP2SpaceArena: SOR_237:1:0
WithP2Resources: 2:SOR_046:1,1:SOR_046:0

## WHEN
- P1>AttackSpaceArena:0:0
- P2>AnswerDecision:YES

## EXPECT
P2RESAVAILABLE:3

---

# WhenDefeated_NoExhaustedResource_NoPrompt
#// SEC_215 Emissary's Sheathipede — "each opponent MAY ready a resource" has nothing to offer when every
#// opponent resource is already ready, so no prompt appears and the defeat resolves cleanly. P2's three
#// resources are all ready before the trade and all three are still ready afterwards.

## GIVEN
CommonSetup: yyw/rrk
WithActivePlayer: 1
WithP1SpaceArena: SEC_215:1:3
WithP2SpaceArena: SOR_237:1:0
WithP2Resources: 3:SOR_046:1

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2RESAVAILABLE:3
P1SPACEARENACOUNT:0
P1NODECISION
P2NODECISION

---

# WhenDefeated_UnderEnemyControl_TheReadyGoesToTheOriginalOwner
#// SEC_215 Emissary's Sheathipede — "each OPPONENT may ready a resource" is resolved relative to the
#// unit's controller at the moment it is defeated. P2 plays JTL_043 No Glory, Only Results on P1's
#// Sheathipede: control moves to P2 first, so the opponent is now P1 and it is P1 who is offered the
#// ready. P1's exhausted resource comes back up; P2's stay as they were.

## GIVEN
CommonSetup: yyw/bbk
WithActivePlayer: 2
WithP2Resources: 6
WithP1Resources: 2:SOR_046:1,1:SOR_046:0
WithP1SpaceArena: SEC_215:1:0
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Drain
- P1>AnswerDecision:YES

## EXPECT
P1RESAVAILABLE:3
P1SPACEARENACOUNT:0
