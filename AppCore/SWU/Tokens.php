<?php
// The canonical list of Star Wars Unlimited token types — the single authority on which tokens
// exist. Consumed by AppCore/SWU/TokenRequirements.php (the "Needs Tokens" deckbuilder readout).
//
// ⚠ NO DEPENDENCIES ON PURPOSE. This file must be loadable alongside EITHER card dictionary.
// SWUDeck's and SWUSim's generated dictionaries declare the same function names, so a process can
// hold only one; the detection code pairs this with SWUDeck's, and the drift test pairs it with
// SWUSim's. A require of either dictionary here would break one of those callers.
//
// DECLARATION ORDER IS THE UI OUTPUT ORDER: upgrades, then units alphabetically, then Credit, then
// The Force. DevTools/tdd-regression/test_swu_token_catalog_drift.php asserts both the membership
// and the order, so a new set's token fails there by name rather than silently going unreported.
//
// Design: docs/superpowers/specs/2026-08-06-swudeck-token-requirements-design.md §1
function SWUTokenCatalog(): array
{
    return [
        // Token Upgrades
        'Shield'        => ['category' => 'upgrade',  'sample' => 'SOR_T02'],
        'Experience'    => ['category' => 'upgrade',  'sample' => 'SOR_T01'],
        'Advantage'     => ['category' => 'upgrade',  'sample' => 'ASH_T02'],
        'Weakness'      => ['category' => 'upgrade',  'sample' => 'HMW_T02'],
        // Token Units, alphabetical
        'Battle Droid'  => ['category' => 'unit',     'sample' => 'TWI_T01'],
        'Beast'         => ['category' => 'unit',     'sample' => 'HMW_T03'],
        'Clone Trooper' => ['category' => 'unit',     'sample' => 'TWI_T02'],
        'Mandalorian'   => ['category' => 'unit',     'sample' => 'ASH_T01'],
        'Spy'           => ['category' => 'unit',     'sample' => 'SEC_T01'],
        'TIE Fighter'   => ['category' => 'unit',     'sample' => 'JTL_T01'],
        'X-Wing'        => ['category' => 'unit',     'sample' => 'JTL_T02'],
        // Resource and Force
        'Credit'        => ['category' => 'resource', 'sample' => 'LAW_T01'],
        'The Force'     => ['category' => 'force',    'sample' => 'LOF_T03'],
    ];
}
