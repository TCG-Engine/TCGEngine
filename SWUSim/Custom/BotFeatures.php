<?php
// Per-seat FEATURE SWITCHES for the heuristic stack (RL bots spec, Section 7: "the strength test"). Owner ruling
// 2026-09-14 — "raise the weak, never lower the strong": every heuristic change must beat the stack it replaces
// head-to-head. So each change of Phase 1b part 2 is named here and checked with SWUBotFeatureOn() where its
// behaviour lives, and a chooser profile can switch some of them off:
//   heuristic-<style>            everything on
//   heuristic-<style>@base       every feature in SWUBotFeatureList() off — the stack before them
//   heuristic-<style>@no-<name>  only <name> off
// The active set belongs to the DECIDING seat: SWUBotHeuristicChoose() sets it at the start of every decision.
// Code outside a decision (unit tests calling a helper directly) sees everything on.

// Phase 1b part 3 (Control piloting and Talzin): each task appends its feature here; '@no-p3' turns all of them off.
const SWU_BOT_PART3_FEATURES = ['dudgate', 'wipegate', 'targeting2', 'modes', 'force', 'setup', 'baserace', 'buffs', 'unique', 'noeffect', 'fodder', 'pilotdeploy', 'plotdeploy'];

function SWUBotFeatureList(): array {
    return array_merge(['splits', 'targeting', 'tags2', 'keep', 'stop', 'enablers', 'picks'], SWU_BOT_PART3_FEATURES);   // Phase 1b part 2, then part 3
}

// Named groups a variant can switch off together: '@no-p3' = the stack as it was after part 2 (run 5).
function SWUBotFeatureGroups(): array {
    return ['p3' => SWU_BOT_PART3_FEATURES];
}

// The features $variant turns off; null for a variant that is not recognised.
function SWUBotVariantDisabled(string $variant): ?array {
    if ($variant === '' || $variant === 'rl') return [];   // 'rl' = the full stack plus the learned layer
    if ($variant === 'base') return SWUBotFeatureList();
    if (str_starts_with($variant, 'no-') && isset(SWUBotFeatureGroups()[substr($variant, 3)])) return SWUBotFeatureGroups()[substr($variant, 3)];
    if (str_starts_with($variant, 'no-') && in_array(substr($variant, 3), SWUBotFeatureList(), true)) return [substr($variant, 3)];
    return null;
}

function SWUBotVariants(): array {
    return array_merge(['base'], array_map(fn($g) => "no-$g", array_keys(SWUBotFeatureGroups())), array_map(fn($f) => "no-$f", SWUBotFeatureList()));
}

function SWUBotSetDisabledFeatures(array $features): void {
    $GLOBALS['SWUBotDisabledFeatures'] = array_values($features);
}

function SWUBotFeatureOn(string $feature): bool {
    return !in_array($feature, $GLOBALS['SWUBotDisabledFeatures'] ?? [], true);
}
