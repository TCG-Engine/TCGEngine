<?php
/** Optional deck goals. Unknown/custom decks measure any legal attack.
 * A readiness predicate describes attack quality, not engine legality.
 */
function PokeOpeningProfile(string $deck): array {
    $profiles = [
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
