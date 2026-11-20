<?php
require dirname(__DIR__,2).'/PokeSim/GeneratedCode/GeneratedCardDictionaries.php';
require dirname(__DIR__,2).'/PokeSim/Custom/DeckImport.php';
require dirname(__DIR__,2).'/PokeSim/Decks/Registry.php';
set_error_handler(function($s,$m,$f,$l){if(error_reporting()&$s)throw new ErrorException($m,0,$s,$f,$l);});
$counts=static function(array $entries): array {
    $result=[];foreach($entries as $entry)$result[$entry['id']]=($result[$entry['id']]??0)+$entry['count'];
    ksort($result);return $result;
};
$_SERVER['REMOTE_ADDR']='127.0.0.1';$checked=0;
foreach(array_keys(PokeDeckRegistry()) as $deckKey){
    $_GET=['deck'=>$deckKey];http_response_code(200);
    ob_start();include dirname(__DIR__,2).'/PokeSim/deck-preview.php';$response=ob_get_clean();
    $payload=json_decode($response,true,512,JSON_THROW_ON_ERROR);
    if(!$payload['ok'])throw new RuntimeException($deckKey.': '.($payload['error']??'Export failed'));
    if($counts(PokeParseDeckText($payload['deckText']))!==$counts(PokeNamedDeck($deckKey)))throw new RuntimeException($deckKey.': Export changed cards or counts');
    if($deckKey==='ceruledge-fighting'&&!str_contains($payload['deckText'],'11 Fighting Energy BS 97'))throw new RuntimeException('Missing Fighting Energy export');
    ++$checked;
}
echo "PASS $checked selectable decks export and reimport with identical cards and counts\n";
