# Reprint_Cunning
#// LAW_028 Canto Bight (Cunning common base) — same Epic Action (reprint). Cunning base + Vigilance/
#//   Heroism leader plays an off-aspect Aggression unit (SEC_161, cost 2) at the printed 2 (Aggression
#//   penalty waived). Confirms a different base in the set shares the wiring.

## GIVEN
CommonSetup: ybw/brk/{
  myBase:LAW_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SEC_161

## WHEN
- P1>UseBaseAbility

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SEC_161
P1RESAVAILABLE:0
P1BASE:EPICUSED

---

# P2Seat_EpicActionPlaysFromTHEIROwnHand
#// COVERAGE: offer=not asserted as a pool (both sections leave exactly one playable card, so the hand
#//           choice auto-resolves) · reqboundary=N/A (with a single playable card no decision is opened,
#//           so no answer crosses a request boundary) ·
#//           control=P2Seat_EpicActionPlaysFromTHEIROwnHand — a BASE can never change control, so seat
#//           resolution is the only observable form of the owner-vs-controller question here ·
#//           boundary=covered on the shared wiring by the LAW_020 Daimyo's Palace file (the same Epic
#//           Action: one pip only, never Villainy/Heroism, no discount when nothing is penalized) ·
#//           decline=covered on the shared wiring by DaimyosPalace.md's two soft-pass sections.
#// LAW_028 — "Play a card from YOUR hand" on a Canto Bight belonging to seat 2. Reprint_Cunning drives
#// the Epic Action from seat 1 only, so a hand lookup pinned to P1 would pass it unchanged. P2 (Cunning
#// base + Vigilance/Villainy leader) holds the off-aspect SEC_161 Contraband Starhopper (Aggression,
#// cost 2, +2 penalty) and P1 holds SOR_128. With exactly 2 resources P2 can afford SEC_161 only if the
#// waiver runs from P2's seat: the Starhopper lands in P2's SPACE arena, P2 is left on 0 resources and
#// P2's Epic is spent, while P1's hand still holds its card and both of P1's arenas stay empty.

## GIVEN
CommonSetup: bbw/ybk/{theirBase:LAW_028}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2Resources: 2
WithP2Hand: SEC_161
WithP1Hand: SOR_128

## WHEN
- P2>UseBaseAbility

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SEC_161
P2RESAVAILABLE:0
P2BASE:EPICUSED
P1HANDCOUNT:1
P2HANDCOUNT:0
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
