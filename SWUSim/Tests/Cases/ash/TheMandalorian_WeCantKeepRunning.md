# Initiative_Decline
#// ASH_014 The Mandalorian — declining the optional payment skips the draw. P1 claims initiative and
#// declines, keeping its resource and drawing nothing.
## GIVEN
CommonSetup: grw/brk/{
  myLeader:ASH_014
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 1
WithP1Deck: SOR_095
## WHEN
- P1>Claim
- P1>AnswerDecision:-
## EXPECT
P1HANDCOUNT:0
P1RESAVAILABLE:1

---

# Initiative_PayDraw
#// ASH_014 The Mandalorian — "When you take the initiative: you may pay 1 resource; if you do, draw a card."
#// P1 claims initiative and accepts, paying 1 resource (1 → 0) to draw SOR_095.
## GIVEN
CommonSetup: grw/brk/{
  myLeader:ASH_014
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 1
WithP1Deck: SOR_095
## WHEN
- P1>Claim
- P1>AnswerDecision:YES
## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:0

---

# Deployed_OnAttack_DrawWithInitiative
#// ASH_014 The Mandalorian (deployed) — On Attack: if you have the initiative, you may draw a
#// card. P1 holds the initiative → may draw → hand 1, deck 0.

## GIVEN
CommonSetup: grw/brk/{
  myLeader:ASH_014:1:1:1
}
SkipPreGame: true
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithActivePlayer: 1
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0

---

# Initiative_NoResources_NoDraw
#// ASH_014 The Mandalorian (leader) — the draw requires paying 1 resource. With 0 resources the payment
#// can't be made, so claiming the initiative draws nothing.
## GIVEN
CommonSetup: grw/brk/{
  myLeader:ASH_014
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 0
WithP1Deck: SOR_095
## WHEN
- P1>Claim
## EXPECT
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# Deployed_OnAttack_NoInitiative_NoDraw
#// ASH_014 The Mandalorian (deployed) — On Attack the draw only happens if YOU have the initiative. Here P2
#// holds the initiative, so when the Mandalorian attacks the enemy SOR_095 no card is drawn.
## GIVEN
CommonSetup: grw/brk/{
  myLeader:ASH_014:1:1:1
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithActivePlayer: 1
WithP1Deck: SOR_095
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1

---

# Initiative_OpponentClaims_NoTrigger
#// ASH_014 The Mandalorian (leader) — the draw only fires when YOU take the initiative. Here P2 takes it,
#// so P1's Mandalorian does not draw even though P1 has resources to spend.
## GIVEN
CommonSetup: grw/brk/{
  myLeader:ASH_014
}
SkipPreGame: true
WithInitiativePlayer: 1
WithActivePlayer: 2
WithP1Resources: 3
WithP1Deck: SOR_095
## WHEN
- P2>Claim
## EXPECT
P1HANDCOUNT:0
P1RESAVAILABLE:3

---

# Deployed_TakeInitiative_NoLeaderSideTrigger
#// ASH_014 The Mandalorian — "When you take the initiative: may pay 1 resource → draw" is printed on the
#// UNDEPLOYED leader side. Once deployed as a leader unit, only the leader-unit-side abilities are active, so
#// claiming the initiative offers no pay/draw prompt: P1 keeps all resources, draws nothing, no pending decision.
## GIVEN
CommonSetup: grw/brk/{
  myLeader:ASH_014:1:1:1
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 3
WithP1Deck: SOR_095
## WHEN
- P1>Claim
## EXPECT
P1HANDCOUNT:0
P1RESAVAILABLE:3
P1NODECISION

---

# Support_DeployedOnAttackDraw_LentToChosenUnit
#// ASH_014 The Mandalorian has Support (when deployed, may attack with another unit; it gains his other
#// abilities for that attack). With initiative, deploying him lets SOR_095 Battlefield Marine make the attack
#// and inherit his On Attack "may draw a card". The Marine hits P2's base for 3 and P1 draws a card (hand 1).
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_014;myResources:12}
SkipPreGame: true
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_095,SOR_046
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:3
P1HANDCOUNT:1

---

# Support_DeployPassesTurn_NoExtraAction
#// ASH_014's Support bonus attack (lent to another unit on deploy) must not leak a free extra action: the
#// deploy is P1's single action, so after the Support attack resolves the turn passes to P2. The bonus
#// attack's combat and the deploy's own (deferred) After Action must not BOTH swap the turn.
#//
#// This is the user-reported scenario. It only surfaces across a real request boundary: the Support attack
#// pauses for a TARGET choice (P2 has a unit), and — when initiative has NOT yet been claimed this round —
#// a fresh-process re-parse reorders the combat vs deferred-resume terminals so both run their After Action.
#// SimulateRequestBoundary reproduces that boundary; without the fix the turn double-swaps back to P1.
## GIVEN
CommonSetup: grw/brk/{myLeader:ASH_014;myResources:12}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Deck: SOR_095,SOR_046
## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:YES
## EXPECT
P2BASEDMG:3
TURNPLAYER:2
