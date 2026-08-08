# GrantedWhenDefeated_CountsRebels
#// SEC_156 Nemik's Manifesto (Upgrade, +1/+1, cost 1, Aggression/Heroism, "Attach to a non-Vehicle unit")
#//   "Attached unit gains the Rebel trait and: 'When Defeated: Deal 1 damage to each enemy base for
#//    each other friendly Rebel unit.'"
#// Host A = SEC_080 (Imperial, NON-Rebel) + Nemik's → 4/4 and Rebel-by-grant. It attacks the 8/8 SOR_039
#// and dies (4 HP < 8 counter), firing its granted When Defeated. Other friendly units:
#//   B = SEC_080 (non-Rebel) + Nemik's → counts ONLY because the manifesto grants it Rebel,
#//   C = SOR_095 (natural Rebel)        → counts,
#//   D = SEC_080 (non-Rebel, no Nemik's) → does NOT count.
#// So "other friendly Rebel units" = B + C = 2 → deal 2 to P2's base. The value 2 (not 1, not 3) proves
#// the trait GRANT works (B counts) AND a plain non-Rebel (D) doesn't. A dies as the ATTACKER so the
#// granted When Defeated drains inside P1's own action.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SEC_156
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 1:SEC_156
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2BASEDMG:2
P1GROUNDARENACOUNT:3
P2GROUNDARENACOUNT:1

---

# NoOtherRebels_NoBaseDamage
#// SEC_156 Nemik's Manifesto — fizzle guard: with no OTHER friendly Rebel unit, the granted When
#// Defeated deals 0. Host A = SEC_080 + Nemik's (4/4, Rebel-by-grant) attacks the 8/8 SOR_039 and dies.
#// The only other friendly unit is D = SEC_080 (non-Rebel, no Nemik's) → 0 other Rebels → no base damage.
#// (A's own granted Rebel doesn't self-count — it's "OTHER friendly Rebel units".)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SEC_156
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2BASEDMG:0
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1

---

# AttachRestriction_NonVehicleOnly
#// SEC_156 Nemik's Manifesto — "Attach to a non-Vehicle unit." With a friendly Vehicle (SOR_232 AT-ST) and
#//   a friendly non-Vehicle (SOR_128) in play, the Vehicle is NOT a legal host, so the only legal target is
#//   SOR_128 — it auto-attaches there and gains the Rebel trait. The AT-ST is untouched (no Rebel grant).

## GIVEN
CommonSetup: rrw/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:0
WithP1GroundArena: SOR_128:1:0
WithP1Hand: SEC_156

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_128
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:HASTRAIT:Rebel
P1GROUNDARENAUNIT:0:CARDID:SOR_232
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:NOTTRAIT:Rebel
P1NODECISION

---

# GrantedWhenDefeated_ResolvesForTheNewControllerAfterTakeControl
#// SEC_156 Nemik's Manifesto — the granted "When Defeated: deal 1 damage to each enemy base for each
#// other friendly Rebel unit" belongs to whoever CONTROLS the host when it dies, and "enemy base" and
#// "friendly Rebel" both re-evaluate for that new controller.
#// P2 plays JTL_043 No Glory, Only Results on P1's SEC_080 host (non-Rebel, made Rebel by the manifesto),
#// taking control and defeating it. The trigger now resolves for P2: P2's other Rebel units are just the
#// one SOR_095, so it deals 1 — and it hits P1's base, which is the enemy base FROM P2'S SIDE.
#// P1's base takes 1 and P2's base takes 0; that asymmetry is the whole point of the section.
#// P1 keeps its remaining SOR_046; the host and the spent event make 2 cards in P1's discard (the host
#// returns to its OWNER's discard even though P2 controlled it at the end).

## GIVEN
CommonSetup: rrk/bbk
WithActivePlayer: 2
WithP2Resources: 6
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SEC_156
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2Hand: JTL_043
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1BASEDMG:1
P2BASEDMG:0
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1DISCARDCOUNT:2
