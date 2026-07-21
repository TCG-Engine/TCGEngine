# UseForce_MassDefeat
#// LOF_039 Darth Sidious (8/8) — When Played: you may use the Force → defeat each non-Sith unit with 3 or
#// less remaining HP. P1 plays him with the Force; the enemy 3/1 and 3/3 (≤3 HP) are defeated, the 3/7
#// survives, and Sidious himself (8 HP) is unaffected.

## GIVEN
CommonSetup: bbk/rrk/{myResources:12;handCardIds:LOF_039}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENACOUNT:1

---

# DeclineForce_NoDefeats
#// LOF_039 Darth Sidious — the When Played mass-defeat is a "may use the Force". P1 holds the Force but
#// DECLINES: no unit is defeated and the Force token is kept. The enemy 3/1 (SOR_128, ≤3 HP) survives.
#// Ref: "allows the player to pass the optional ability".

## GIVEN
CommonSetup: bbk/rrk/{myResources:12;handCardIds:LOF_039}
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1HASFORCE
P2GROUNDARENACOUNT:1

---

# MassDefeat_CrossArena_SithExempt
#// LOF_039 Darth Sidious — the mass-defeat hits EACH non-Sith unit with ≤3 HP across BOTH players and BOTH
#// arenas, and spares Sith units. Friendly Sith Trooper (JTL_238, 3/3, Sith → exempt) survives; friendly
#// non-Sith Marine (SOR_095, 3/3) is defeated; enemy Consular Security Force (SOR_046, 3/7 > 3 HP) survives;
#// enemy space A-Wing (SOR_141, 1/3) is defeated. Sidious himself (Sith, 8 HP) survives. Ref: Sith units and
#// >3-HP units remain; non-Sith ≤3-HP units in every zone are defeated.

## GIVEN
CommonSetup: bbk/rrk/{myResources:12;handCardIds:LOF_039}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: JTL_238:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_141:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2SPACEARENACOUNT:0
