<?php
function PokeDeckRegistry(): array { return ['sinistcha'=>'Dhelmise / Sinistcha','dhelmise-v2'=>'dhelmise v2','brisbane-lopunny'=>'Brisbane Lopunny','relicanth-fossils'=>'Relicanth / Fossils','relicanth-v2-draw'=>'relicanth v2 - draw','relicanth-v3-meta-tune'=>'Relicanth-v3-meta-tune','relicanth-v4-colress'=>'Relicanth v4 - Colress','relicanth-v5-bastiodon'=>'Relicanth v5 - Bastiodon','relicanth-v6-explorers-guidance'=>"Relicanth v6 - Explorer's Guidance",'relicanth-v7-lanas-aid'=>"Relicanth v7 - Lana's Aid"]; }
function PokeDeckName(string $key): string { return PokeDeckRegistry()[$key]??'Custom deck'; }
function PokeNamedDeck(string $key): array {
    if (!isset(PokeDeckRegistry()[$key])) throw new InvalidArgumentException('Unknown deck');
    return PokeParseDeckText(file_get_contents(__DIR__.'/'.$key.'.txt'));
}
function PokeDetectDeck(array $deck): string {
    $counts=static function(array $cards): array { $out=[];foreach($cards as $card)$out[$card['id']]=($out[$card['id']]??0)+$card['count'];ksort($out);return $out; };
    if($counts($deck)===$counts(PokeNamedDeck('relicanth-v7-lanas-aid')))return 'relicanth-v7-lanas-aid';
    if($counts($deck)===$counts(PokeNamedDeck('relicanth-v6-explorers-guidance')))return 'relicanth-v6-explorers-guidance';
    if($counts($deck)===$counts(PokeNamedDeck('relicanth-v5-bastiodon')))return 'relicanth-v5-bastiodon';
    if($counts($deck)===$counts(PokeNamedDeck('relicanth-v4-colress')))return 'relicanth-v4-colress';
    if($counts($deck)===$counts(PokeNamedDeck('relicanth-v3-meta-tune')))return 'relicanth-v3-meta-tune';
    if (in_array('me05-017',array_column($deck,'id'),true)) return array_intersect(['sv07-139','me02.5-190','sv06-158','sv06-167'],array_column($deck,'id'))?'relicanth-v2-draw':'relicanth-fossils';
    if (in_array('me02-084',array_column($deck,'id'),true)) return 'brisbane-lopunny';
    if($counts($deck)===$counts(PokeNamedDeck('dhelmise-v2')))return 'dhelmise-v2';
    return 'sinistcha';
}
