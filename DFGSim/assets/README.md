# Arena art

`prototype-arena.png` is the active static decorative background, generated with the built-in imagegen tool in a rough placeholder style: flat muted colors, imperfect outlines, simplified faceless sparring figures, and sparse courtyard scenery. It is AI-generated placeholder art, not commissioned human artwork. Health, round, commitments, and controls are rendered as live HTML over it. The illustrated fighters are atmosphere and do not represent a player's current hand color or a selectable character.

The art layer is isolated in `.arena-art` so a later character/animation system can replace the composite background with separate stage and fighter assets. No move playback or character selection is implemented yet.

The earlier polished `temple-arena.png` is retained as an unused source reference. The replacement keeps its broad composition but removes detailed costumes, energy effects, cinematic lighting, and realistic rendering.

Original reference generation prompt:

> Use case: stylized-concept. Asset type: wide background illustration for a browser fighting card game. Create a cinematic 2.7:1 wide composition, inked painterly fighting game key art. Ancient ruined temple columns at dusk, distant mountains, amber sky, dark slate stone platform. Two original martial artists facing each other: on the far left a powerful woman with tied black hair, crimson sleeveless training outfit, wrapped fists in a guarded stance facing right, red energy brushwork; on the far right a lean male with short windswept dark hair, dark violet layered training outfit and wrapped fists facing left, violet energy brushwork. Fighters occupy outer left and right thirds, seen from thighs up, leave the entire middle third as relatively quiet scenery for two UI cards. Dramatic strong silhouettes, hand-painted comic ink, high contrast, red and violet rim lights. Top 20 percent dark quiet sky for health bar overlay. No text, no logos, no cards, no interface, no watermark. The image is decorative static arena art; all game UI will be real HTML layered over it.
