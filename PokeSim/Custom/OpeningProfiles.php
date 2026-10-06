<?php
/** Optional deck goals. Unknown/custom decks measure any legal attack.
 * A readiness predicate describes attack quality, not engine legality.
 */
function PokeOpeningProfile(string $deck): array {
    if($deck==='dhelmise-v2')$deck='sinistcha';
    if(in_array($deck,['relicanth-v2-draw','relicanth-v3-meta-tune','relicanth-v4-colress','relicanth-v5-bastiodon','relicanth-v6-explorers-guidance','relicanth-v7-lanas-aid'],true))$deck='relicanth-fossils';
    $profiles = [
        'relicanth-fossils' => ['name'=>'Fossil Beatdown', 'targets'=>['me05-017'=>[0]],
            'ready'=>static fn(int $player, $obj): bool => PokeFossilBenchCount($player) >= 4,
            'milestones'=>static fn(int $player): array => ['fourFossils'=>PokeFossilBenchCount($player) >= 4]],
        'sinistcha' => ['name'=>'Vengeful Anchor', 'targets'=>['me05-039'=>[0]],
            'ready'=>static fn(int $player, $obj): bool => PokeCountHideSneak($player) >= 4,
            'milestones'=>static fn(int $player): array => ['hideSneakFour'=>PokeCountHideSneak($player) >= 4]],
        'brisbane-lopunny' => ['name'=>'Gale Thrust', 'targets'=>['me02-084'=>[0]],
            'ready'=>static fn(int $player, $obj): bool => PokeMovedToActiveThisTurn(PokeRef($player, $obj->Location, $obj->mzIndex)),
            'milestones'=>static fn(int $player): array => []],
    ];
    return $profiles[$deck] ?? ['name'=>'Any attack', 'targets'=>[],
        'ready'=>static fn(int $player, $obj): bool => true,
        'milestones'=>static fn(int $player): array => []];
}
