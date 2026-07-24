<?php

function AzukiRlBotProfiles(): array {
    return [
        'raizan' => [
            'label' => 'Raizan Starter Deck',
            'deck' => 'Raizan',
            'model' => 'raizan-aggro-control-20260712-194552-ep7040.json',
        ],
        'zero' => [
            'label' => 'Zero (Deck 51)',
            'deck' => 'ZeroRL',
            'model' => 'zero-aggro-control-20260724-160200-ep30000.json',
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
