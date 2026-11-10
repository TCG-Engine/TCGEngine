# FromHand_C3POPaysTheCommandPenalty_NotPlayableWithOneResource
#// LAW_030 Partisan Hideout (Cunning common base) — Epic Action: Play a card from your hand, ignoring 1 of its
#//   Vigilance/Command/Aggression/Cunning aspect penalties.
#// Bug report P-28 (game 5201): "I still have one resource and it won't let me play C-3PO." The reported board:
#//   SEC_005 Satine Kryze (Vigilance/Heroism) on Partisan Hideout (Cunning), 1 ready resource, hand
#//   [SHD_201 Principled Outlaw 4, SEC_093 C-3PO, SEC_203 Tala Durith 3]. SEC_093 is Command/Heroism, cost 1 —
#//   the Command pip is uncovered, so FROM HAND it costs 1 + 2 = 3: not playable, nothing spent.

## GIVEN
CommonSetup: bbw/brk/{
  myLeader:SEC_005;
  myBase:LAW_030
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1Hand: [SHD_201 SEC_093 SEC_203]

## WHEN
- P1>PlayHand:1

## EXPECT
P1NODECISION
P1HANDGLOWNOT:1
P1HANDCOUNT:3
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:1

---

# ViaTheEpicAction_C3POIsTheOnlyOffer_PlaysForOne
#// The same board through the base's Epic Action: the Command pip is waived, so C-3PO costs its printed 1 and is
#// the only card in hand the base can play with 1 ready resource (Principled Outlaw 4 and Tala Durith 3 have no
#// penalty to waive). It is played, paying the 1, and the Epic Action is used.

## GIVEN
CommonSetup: bbw/brk/{
  myLeader:SEC_005;
  myBase:LAW_030
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1Hand: [SHD_201 SEC_093 SEC_203]

## WHEN
- P1>UseBaseAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_093
P1HANDCOUNT:2
P1RESAVAILABLE:0
P1BASE:EPICUSED
