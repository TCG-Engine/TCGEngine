<?php
// The SWU sets that were sold in booster packs — the only sets Limited (Sealed / Draft) can open.
// Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §2.2.
//
// Release order; the LAST entry is the "latest main set" a new Limited room defaults to.
// Not here on purpose: IBH and TS26 (pre-built decks, no boosters) and IC27 (Icons packs, officially not for
// Limited — it is a SUPPLEMENTAL set below) — and no preview set until it releases.
//
// Per set:
//   era            the collation era its pull rates come from (SWULimitedEraOdds):
//                    classic = SOR–TWI · jtl = JTL–SEC (20 legendaries, S cards in foil/hyperspace) ·
//                    law = LAW onward (foil slot always hyperspace-foil, guaranteed hyperspace common)
//   rareSlotBases  true = Rare bases are opened in the RARE slot. Official ("A Shift from What Was"): LAW is the
//                  last set that does this; from ASH on any base with an ability goes in the BASE slot.
//
// Adding a set (e.g. Set 10): append one entry, pick its era (a new era needs its odds in SWULimitedEraOdds),
// and set rareSlotBases to false.
function SWUBoosterSets(): array {
    return [
        'SOR' => ['era' => 'classic', 'rareSlotBases' => true],
        'SHD' => ['era' => 'classic', 'rareSlotBases' => true],
        'TWI' => ['era' => 'classic', 'rareSlotBases' => true],
        'JTL' => ['era' => 'jtl',     'rareSlotBases' => true],
        'LOF' => ['era' => 'jtl',     'rareSlotBases' => true],
        'SEC' => ['era' => 'jtl',     'rareSlotBases' => true],
        'LAW' => ['era' => 'law',     'rareSlotBases' => true],
        'ASH' => ['era' => 'law',     'rareSlotBases' => false],
        'HMW' => ['era' => 'law',     'rareSlotBases' => false],
    ];
}

function SWUIsBoosterSet(string $set): bool {
    return isset(SWUBoosterSets()[$set]);
}

function SWUBoosterSetEra(string $set): ?string {
    return SWUBoosterSets()[$set]['era'] ?? null;
}

function SWUBoosterSetRareSlotBases(string $set): bool {
    return !empty(SWUBoosterSets()[$set]['rareSlotBases']);
}

function SWULatestBoosterSet(): string {
    $keys = array_keys(SWUBoosterSets());
    return end($keys);
}

// SUPPLEMENTAL packs (owner 2026-11-05): smaller packs that are not a main set's booster — offered in Host's Choice only
// (never Single Set, Chaos or the "latest" default), so a host can mix them in with rules of their own (e.g. 3 HMW +
// 5 Icons for Sealed). Each names its collation (SWUSim/Limited/LimitedPacks.php). IC27 is listed while it previews.
//   icons  Icons (IC27): 9 cards — 3 C, a slot that is a Leader 25% of the time (else a C), a hyperspace C, 2 U, 1 R/L,
//          a foil of any rarity; no base slot. Reprints collate by the rarity printed in the set (PrintingRarities.php).
function SWUSupplementalBoosterSets(): array {
    return [
        'IC27' => ['collation' => 'icons'],
    ];
}

function SWUIsSupplementalBoosterSet(string $set): bool {
    return isset(SWUSupplementalBoosterSets()[$set]);
}

// Every set a Host's Choice lineup may name: the main booster sets in release order, then the supplemental ones.
function SWUHostChoiceSets(): array {
    return array_merge(array_keys(SWUBoosterSets()), array_keys(SWUSupplementalBoosterSets()));
}

function SWUIsHostChoiceSet(string $set): bool {
    return SWUIsBoosterSet($set) || SWUIsSupplementalBoosterSet($set);
}
