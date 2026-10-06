<?php
// Read-only deck metadata; use the same local access boundary as the menu.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit;
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    require_once __DIR__.'/GeneratedCode/GeneratedCardDictionaries.php';
    require_once __DIR__.'/Custom/DeckImport.php';
    require_once __DIR__.'/Decks/Registry.php';
    $key = $_GET['deck'] ?? '';
    if (!is_string($key) || !isset(PokeDeckRegistry()[$key])) {
        throw new InvalidArgumentException('Unknown deck');
    }
    $cards = [];
    foreach (PokeNamedDeck($key) as $entry) {
        $id = $entry['id'];
        if (!isset($cards[$id])) {
            $cards[$id] = ['id'=>$id, 'name'=>CardName($id), 'type'=>CardType($id), 'count'=>0];
        }
        $cards[$id]['count'] += $entry['count'];
    }
    // Preserve the original export's printings (including MEE 13), while
    // converting internal TCGdex IDs to the set-code format Limitless accepts.
    $setCodes = array_flip(POKE_SET_CODES) + ['sv06'=>'TWM', 'sv06.5'=>'SFA'];
    $deckText = preg_replace_callback('/^(\d+)\s+([a-z][a-z0-9._]*-[A-Za-z0-9]+)\s*$/m',
        static function (array $match) use ($setCodes): string {
            $id = $match[2];
            $set = $setCodes[CardSet($id)] ?? null;
            if ($set === null) throw new RuntimeException('Missing export set code for '.$id);
            return $match[1].' '.CardName($id).' '.$set.' '.(int)CardLocalId($id);
        }, str_replace("\r\n", "\n", file_get_contents(__DIR__.'/Decks/'.$key.'.txt')));
    echo json_encode(['ok'=>true, 'name'=>PokeDeckName($key), 'cards'=>array_values($cards),
        'deckText'=>trim($deckText)."\n"], JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'error'=>$error->getMessage()]);
}
