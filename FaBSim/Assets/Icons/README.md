# FaBSim UI icons

`health.png`, `resource.png`, `attack.png`, and `defense.png` are copied unchanged
from the local legacy client, `FaB-Online-React-Client/src/img/symbols/symbol-*.png`.

`energy.png` is copied unchanged from that client's `symbol-energyCounter.png`.
It supplies centered charge/energy counters; `defense.png` also supplies the
bottom-right equipment defense counters, including negative armor values.

`chain.svg`, `action.svg`, and `chi.svg` are original vector UI artwork for this
layout. They remain editable and scale to small counters without raster blur.

`phases.svg` is the original phase-tracker symbol set: stacked layers, sword,
shield, reaction arrows, damage fracture, resolution seal, and separated chain.
It uses `currentColor` to retain the tracker’s completed/active/priority colors.
