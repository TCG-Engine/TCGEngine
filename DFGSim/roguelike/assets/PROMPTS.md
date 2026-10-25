# Current creature art — style revision

Generated with the built-in image-generation tool, using `DFGSim/assets/fighter-red-transparent.webp` as the direct style reference. The new sprites use the existing fighters' thick outlines, simple shapes, flat color, and sparse detail.

Current saved assets:

- `DFGSim/roguelike/assets/fox-v2.png`
- `DFGSim/roguelike/assets/tortoise-v2.png`
- `DFGSim/roguelike/assets/tengu-v2.png`
- `DFGSim/roguelike/assets/dragon-v2.png`

The painterly atlas is retired and no longer used. The shared fight page retains the original player's animation clips; creatures use six generated animations each through the shared player playback controller. See `animations/README.md` for input prompts, source videos, conversion settings, and verification.

## fox

Style-reference edit. Use the attached existing red fighter as the STRICT visual style reference, not a character to preserve. Replace the human with a russet fox martial artist with pointed ears, a simple angular muzzle, three stylized flame-shaped tails, cream sleeveless gi, amber sash, brown trousers. Preserve the reference's simplified cartoon game-sprite style: very thick dark near-black outlines, large uncomplicated geometric shapes, broad flat color fills, at most one flat shadow tone per shape, chunky hands and feet, simple cloth folds and minimal facial details. Exactly the same level of abstraction and line weight as the reference. Full-body martial arts guard stance facing LEFT, fully visible with generous transparent padding on all sides. Original mythological creature identity must read clearly. Transparent background. No photorealism, no painting, no brush texture, no fur/scales micro-detail, no elaborate embroidery, no atmospheric lighting, no scenery, no words. ONE character only, no atlas. Match the reference's approachable cartoon silhouette.

## tortoise

Style-reference edit. Use the attached existing red fighter as the STRICT visual style reference, not a character to preserve. Replace the human with a jade-green tortoise martial artist with a large simple geometric shell, rounded sturdy limbs, olive sleeveless gi, dark green sash and brown trousers. Preserve the reference's simplified cartoon game-sprite style: very thick dark near-black outlines, large uncomplicated geometric shapes, broad flat color fills, at most one flat shadow tone per shape, chunky hands and feet, simple cloth folds and minimal facial details. Exactly the same level of abstraction and line weight as the reference. Full-body martial arts guard stance facing LEFT, fully visible with generous transparent padding on all sides. Original mythological creature identity must read clearly. Transparent background. No photorealism, no painting, no brush texture, no fur/scales micro-detail, no elaborate embroidery, no atmospheric lighting, no scenery, no words. ONE character only, no atlas. Match the reference's approachable cartoon silhouette.

## tengu

Style-reference edit. Use the attached existing red fighter as the STRICT visual style reference, not a character to preserve. Replace the human with a white crane tengu martial artist with a simple long beak, black wing-like forearms, one raised knee, indigo sleeveless gi, dark sash and brown trousers. Preserve the reference's simplified cartoon game-sprite style: very thick dark near-black outlines, large uncomplicated geometric shapes, broad flat color fills, at most one flat shadow tone per shape, chunky hands and feet, simple cloth folds and minimal facial details. Exactly the same level of abstraction and line weight as the reference. Full-body martial arts guard stance facing LEFT, fully visible with generous transparent padding on all sides. Original mythological creature identity must read clearly. Transparent background. No photorealism, no painting, no brush texture, no fur/scales micro-detail, no elaborate embroidery, no atmospheric lighting, no scenery, no words. ONE character only, no atlas. Match the reference's approachable cartoon silhouette.

## dragon

Style-reference edit. Use the attached existing red fighter as the STRICT visual style reference, not a character to preserve. Replace the human with an azure dragon martial arts master with a simple blue dragon head, small antlers, short whiskers, a curved tail, navy sleeveless gi, gold sash and brown trousers. Preserve the reference's simplified cartoon game-sprite style: very thick dark near-black outlines, large uncomplicated geometric shapes, broad flat color fills, at most one flat shadow tone per shape, chunky hands and feet, simple cloth folds and minimal facial details. Exactly the same level of abstraction and line weight as the reference. Full-body martial arts guard stance facing LEFT, fully visible with generous transparent padding on all sides. Original mythological creature identity must read clearly. Transparent background. No photorealism, no painting, no brush texture, no fur/scales micro-detail, no elaborate embroidery, no atmospheric lighting, no scenery, no words. ONE character only, no atlas. Match the reference's approachable cartoon silhouette.

# Retired original atlas

Generated using the built-in image generation tool with a transparent background. No external generation API or runtime key is used.

Saved project asset: `DFGSim/roguelike/assets/creature-atlas.png` (1272 × 1236, PNG with alpha).

The original atlas is preserved. Its four creatures are framed individually in the interface with SVG viewports, with CSS animations for idle, attack, hit, block, and defeat. The hero and arena reuse existing DFGSim assets.

## Exact prompt

Use case: stylized-concept. Asset type: a 2 by 2 sprite atlas of FOUR original mythological martial artists for a playable card fighting roguelike, no UI, no letters, no borders, no captions. Four equal square cells, each cell a separate full-body character with all anatomy fully inside its cell and generous padding; all characters face LEFT toward an opponent, feet same relative baseline. TOP LEFT: an anthropomorphic ember fox martial artist, russet fur, flowing cream kung-fu robes, amber sash, agile open palm stance, three flame-like tails. TOP RIGHT: a mighty jade tortoise warrior, moss-green shell, dark green linen trousers and wraps, grounded defensive kung-fu stance. BOTTOM LEFT: a white crane tengu monk, long neck and beak, black feathered wing arms, indigo martial robes and sash, precise poised kick stance. BOTTOM RIGHT: azure dragon master, tall serpentine dragon head with whiskers and antlers, teal scales, dark blue layered martial robes, commanding claw-palm stance, imposing boss silhouette. Style: elegant expressive Chinese brush-ink martial arts illustration with broad painterly grayscale shading and restrained individual color accents, richly textured but clean recognizable silhouettes. All four match the same art direction, no weapons, no gore, premium game illustration rather than cute cartoons. Transparent background for entire atlas, empty transparent space between figures. Do not overlap cells. Four illustrations, not twelve animation frames.
