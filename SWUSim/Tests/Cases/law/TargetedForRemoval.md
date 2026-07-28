# OppCreditsOnDefeat
#// LAW_141 Targeted For Removal (Upgrade, +0/+0) — grants "When Defeated: An opponent creates Credit
#// tokens equal to this unit's cost." SEC_080 (cost 2) wears it and attacks the 8/8 SOR_039, dying. Its
#// granted When Defeated fires → P2 (the opponent) creates 2 Credit tokens (= SEC_080's cost).

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_141
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2CREDITCOUNT:2

---

# ReturnedToHand_NoCredits
#// LAW_141 Targeted For Removal — the granted ability is "When Defeated", so returning the attached unit to
#// hand (not a defeat) does NOT trigger it. P2's SEC_080 wears the upgrade; P1 plays Waylay (SOR_222) to
#// bounce it to P2's hand. No Credit tokens are created for either player.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:LAW_141
WithP1Hand: SOR_222

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1CREDITCOUNT:0
P2CREDITCOUNT:0
