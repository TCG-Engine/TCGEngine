<?php
function PokeDeckRegistry(): array { return ['sinistcha'=>'Dhelmise / Sinistcha','brisbane-lopunny'=>'Brisbane Lopunny']; }
function PokeDeckName(string $key): string { return PokeDeckRegistry()[$key]??'Custom deck'; }
function PokeNamedDeck(string $key): array {
    if (!isset(PokeDeckRegistry()[$key])) throw new InvalidArgumentException('Unknown deck');
    return PokeParseDeckText(file_get_contents(__DIR__.'/'.$key.'.txt'));
}
function PokeDetectDeck(array $deck): string {
    if (in_array('me02-084',array_column($deck,'id'),true)) return 'brisbane-lopunny';
    return 'sinistcha';
}
