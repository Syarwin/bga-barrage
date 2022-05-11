import { checkIfActionPossible, getCurrentPlayerId, sendAction } from "./framework/utils"

export const pickTile = checkIfActionPossible("pickTile", e => {
    sendAction("pickTile", { tileId: e.target.dataset.id });
})

/*
export const doneResolveDisaster = checkIfActionPossible("resolveDisaster", () => {
    const selectedTiles = document.querySelectorAll(`#player_tiles_${getCurrentPlayerId()} .tile.selected`);
    const tileIds = Object.values(selectedTiles).map(tile => {
        return tile.dataset.id;
    }).join(";");
    sendAction("resolveDisaster", {
        tiles: tileIds
    });
});
*/
