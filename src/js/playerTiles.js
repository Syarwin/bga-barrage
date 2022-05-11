import { spendGodTile } from "./actions";
import { attach, detachAll } from "./framework/event";
import { getCurrentPlayerId, orderPlayersWithCurrentPlayerFirst } from "./framework/utils";
import * as templates from "./templates";

export const setupPlayerTiles = (playerTiles, players) => {
    const parent = document.getElementById('player_tiles');
    players = orderPlayersWithCurrentPlayerFirst(Object.values(players));

    for (const player of players) {
        parent.insertAdjacentHTML("beforeend", templates.playerTiles(player));
    }

    for (const tile of Object.values(playerTiles)) {
        addPlayerTile(tile);
    }
}

export const addPlayerTile = tile => {
    const playerId = +tile.location_arg;

    let categoryType = tile.type;

    if (tile.type == "river" && tile.type_arg == 2) {
        categoryType = "flood";
    }

    const playerTilesElement = document.querySelector(`#player_tiles_${playerId} .tile-category.${categoryType}`);

    if (playerTilesElement) { //disaster is not displayed in the player tiles
        playerTilesElement.insertAdjacentHTML("beforeend", templates.tile(tile.id, tile.type, tile.type_arg));

        const multiplier = tile.type == "monument" ? 2 : 1;
        if (playerTilesElement?.childElementCount > 16 * multiplier) {
            playerTilesElement.classList.add("ultra-compact");
            playerTilesElement.classList.remove("compact");
        } else if (playerTilesElement?.childElementCount > 6 * multiplier) {
            playerTilesElement.classList.add("compact");
            playerTilesElement.classList.remove("ultra-compact");
        } else {
            playerTilesElement.classList.remove("compact");
            playerTilesElement.classList.remove("ultra-compact");
        }
    }
    
}

export const activateGodTiles = () => {
    activateTilesOfType("god", spendGodTile, "spendGodTile");
}


export const activateTilesOfType = (type, handler, eventGroup) => {
    let tiles = document.querySelectorAll(`#player_tiles_${getCurrentPlayerId()} .tile-category.${type} .tile`);

    if (type == "river") {
        tiles = [
            ...tiles,
            ...document.querySelectorAll(`#player_tiles_${getCurrentPlayerId()} .tile-category.flood .tile`)
        ];
    }

    for (const tile of tiles) {
        tile.classList.add("active");

        attach(tile, handler, "click", eventGroup);
    }
}

export const deactivateGodTiles = () => {
    deactivateActiveTiles("spendGodTile");
}

export const deactivateActiveTiles = (eventGroup) => {
    const tiles = document.querySelectorAll(`#player_tiles_${getCurrentPlayerId()} .tile-category .tile.active`);

    for (const tile of tiles) {
        tile.classList.remove("active");
        tile.classList.remove("selected");
    }

    detachAll(eventGroup);
}

export const removePlayerTile = (tileId, playerId) => {
    const tile = document.querySelector(`#player_tiles_${playerId} .tile[data-id="${tileId}"]`);
    tile.parentElement.removeChild(tile);
}