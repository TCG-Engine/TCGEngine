# Fighting Card template draft

`dfg-template.json` is the new template saved to hosted game 1 / template 1. `dfg-template-before.json` preserves the previous fields and layout. Existing field IDs and all card rules/values were preserved; artwork is the new optional image field (ID 11).

Open `template-preview.html` to inspect Quick Jab, Counterpunch, and Chain Grapple using the actual shared renderer and local card data. Illustrations have not been assigned; the artwork window currently displays a placeholder. Select an uploaded image in each card's **Card artwork** field to fill that window.

The remote template and artwork field are already saved. Deploy these two frontend files to the matching locations on the remote server, then hard-refresh the Card Editor:

- `CardEditor/UI/PreviewRenderer.js`
- `CardEditor/UI/TemplateCanvas.js`

The old frontend shows switch colors as text. The new renderer shows the same multiselect values as colored orbs with readable color names; no duplicated per-card color data is required. The layout inspector includes orb size, individual option colors, and section captions. Other multiselect fields retain their existing text rendering unless explicitly configured for orbs.

Layout `style_json` options: `displayMode: "colorOrbs"` on multiselect fields, `orbColors` mapping option names to six-digit hex colors, `orbSize` from 16–100 pixels, `caption` above text, `emptyText` for empty text fields, and `placeholderText` for unassigned artwork. These are renderer options rather than CSS properties. Unknown orb colors appear as readable text. The six colors are accompanied by names for accessibility.

Run `node DevTools/CardEditor/template-preview-test.mjs` with Playwright available under `DevTools/ui-harness/node_modules` and `PLAYWRIGHT_BROWSERS_PATH` set to the installed browser directory. The test renders all 36 cards, checks overflow, verifies switch counts, zero stats, and assigned artwork, and regenerates the HTML and screenshots. Chromium and WebKit passed; Firefox was not verified in this run. Set `CARD_TEMPLATE_BROWSERS=chromium,firefox,webkit` to include Firefox on a supported installation.
