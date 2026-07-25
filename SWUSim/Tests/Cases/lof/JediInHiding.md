# UseForce_OpponentDiscards
#// LOF_159 Jedi In Hiding (3/3) — Hidden + When Defeated: may use the Force → each opponent discards a
#// card. It attacks a 4/7 and dies to the counter; on death P1 uses the Force and P2 discards their only
#// card.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_159:1:0
WithP2GroundArena: LAW_124:1:0
WithP2Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P2HANDCOUNT:0

---

# NoForce_NoDiscard
#// LOF_159 Jedi In Hiding — the When Defeated discard requires paying the Force. With no Force token, the
#// "may use the Force" ability can't be paid, so nothing happens and the opponent keeps their card. Jedi
#// attacks a 4/7 (LAW_124) and dies to the counter, but P2's hand is untouched. Ref: "does not allow to
#// discard a card from opponent's hand if player does not have the force".

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_159:1:0
WithP2GroundArena: LAW_124:1:0
WithP2Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2HANDCOUNT:1

---

# NoGloryControlChange_ThisPlayerDiscards
#// LOF_159 Jedi In Hiding — the When Defeated ability follows whoever CONTROLS the unit at defeat. P2 plays
#// No Glory, Only Results (JTL_043) to take control of Jedi In Hiding and defeat it; the WD now belongs to
#// P2, who pays THEIR Force and forces the "opponent" (from P2's view = P1) to discard a card. P2 loses the
#// Force; P1 discards. Ref: "may allow the opponent to use the force because of No Glory Only Results ...
#// player discards a card from their hand". (JTL_043 auto-targets the only non-leader unit.)

## GIVEN
CommonSetup: rrk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: LOF_159:1:0
WithP1Hand: SOR_095 SOR_164
WithP2Resources: 5
WithP2Force: true
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:YES
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:0
P2NOFORCE
P1HANDCOUNT:1
