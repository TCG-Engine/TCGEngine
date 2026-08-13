<?php

function AzukiRlBotProfiles(): array {
    return [
        'raizan' => [
            'label' => 'Raizan (Deck 373)',
            'deck' => 'RaizanRL',
            'model' => 'raizan-aggro-control-20260712-194552-ep7040.json',
        ],
        'zero' => [
            'label' => 'Zero (Deck 51)',
            'deck' => 'ZeroRL',
            'model' => 'zero-residual-deck51-20260801-ep125040.json',
        ],
        'bobu' => [
            'label' => 'Bobu (Deck 241)',
            'deck' => 'BobuRL',
            'model' => 'bobu-residual-deck241-20260806-ep150000.json',
        ],
        'goldfish' => [
            'label' => 'Goldfish (Auto-pass)',
            'deck' => 'Raizan',
            'model' => '',
        ],
    ];
}

function NormalizeAzukiRlBotProfile($profile): string {
    $normalized = strtolower(trim(strval($profile)));
    return array_key_exists($normalized, AzukiRlBotProfiles()) ? $normalized : 'raizan';
}

function GetAzukiRlBotProfile($profile): array {
    $profiles = AzukiRlBotProfiles();
    return $profiles[NormalizeAzukiRlBotProfile($profile)];
}
