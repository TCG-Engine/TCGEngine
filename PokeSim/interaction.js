// Presentation adapter: target availability comes exclusively from the engine's
// offered actions. Never infer attachment or evolution legality from card data.
(function (root) {
    function targets(state, source) {
        return (state?.actions || []).filter(action =>
            action.source === source && action.target &&
            (action.type === 'attach' || action.type === 'evolve'));
    }
    function actionsForCard(state, ref) {
        const activeSeat = /^p([12])Active-/.exec(ref || '')?.[1];
        return (state?.actions || []).filter(action =>
            action.source === ref ||
            (action.type === 'retreat' && activeSeat &&
                Number(action.player) === Number(activeSeat)));
    }
    function click(state, selected, clicked) {
        const action = targets(state, selected).find(action => action.target === clicked);
        if (action) return {action};
        // Keep a selected source while inspecting an invalid field target.
        if (targets(state, selected).length && /^(p[12])(Active|Bench)-/.test(clicked))
            return {selected};
        if (selected === clicked) return {selected:null};
        // Global moves (ready/end) and actions targeting this card from some
        // other source do not count as actions belonging to the clicked card.
        const actions = actionsForCard(state, clicked);
        // Attacking commits the turn's combat decision and always needs an
        // explicit action-button click, even when only one attack is legal.
        if (actions.length === 1 && actions[0].type !== 'attack') return {action:actions[0]};
        return {selected: selected === clicked ? null : clicked};
    }
    const api = {targets, actionsForCard, click};
    if (typeof module !== 'undefined' && module.exports) module.exports = api;
    else root.PokeInteraction = api;
})(typeof window !== 'undefined' ? window : globalThis);
