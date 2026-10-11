<?php
// HMW_119 Saw Gerrera — Unit (Ground) 3/6, cost 4.
// "When an opponent plays an event: Resource the top card of your deck."
// The reaction lives in _SWUCollectOpponentPlayReactionsFor (GameLogic) — mandatory, so it resolves at
// collection time, after the event's own effects. A blanked Saw (SEC_046 / lost abilities) does not react.
