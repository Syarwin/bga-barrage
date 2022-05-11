import { disableScore, getCurrentPlayerId, isCurrentPlayer, orderPlayersWithCurrentPlayerFirst } from './framework/utils';
import * as template from './templates';

const players = {};

const createSun = (parent, type = null, rotated = null) => {
    parent.insertAdjacentHTML("beforeend", template.sun(type, rotated));
}

const initPlayer = (player, position) => {
    players[player.id] = player;

    const playerArea = document.getElementById(`player_board_${player.id}`);

    createPlayerPanel(playerArea, player);

    const biddingArea = document.getElementById("bidding");
    createPlayerBiddingArea(biddingArea, player, position);
}

const playerPositionsByCount = {
    2: [ "bottom", "top" ],
    3: [ "bottom", "top-left", "top-right" ],
    4: [ "bottom", "left", "top", "right" ],
    5: [ "bottom", "left", "top-left", "top-right", "right" ],
}

const createPlayerPanel = (parent, player) => {
    parent.insertAdjacentHTML("beforeend", template.playerPanel(player));

    const playerSuns = document.getElementById(`player_suns_${player.id}`);

    for (const sun of player.suns) {
        createSun(playerSuns, sun);
    }
    
    for (let i = 0; i < player.usedSuns; i++){
        createSun(playerSuns);
    }
}

const createPlayerBiddingArea = (parent, player, position) => {
    parent.insertAdjacentHTML("beforeend", template.playerBiddingArea({
        ...player,
        name: player.id == getCurrentPlayerId() ? "you" : player.name
    }, position));

    const playerBiddingSuns = document.querySelector(`#player-bidding-area-${player.id} .player-suns`);

    let rotated = null;
    if (position == "left") {
        rotated = "right";
    }
    if (position == "right") {
        rotated = "left";
    }

    for (const sun of player.suns) {
        createSun(playerBiddingSuns, sun, rotated);
    }
    
    for (let i = 0; i < player.usedSuns; i++){
        createSun(playerBiddingSuns);
    }
}

export const initPlayers = (players, currentPlayerId) => {
    players = orderPlayersWithCurrentPlayerFirst(players);

    const playerPositions = playerPositionsByCount[players.length]; 

    for (const player of players) {
        initPlayer(player, playerPositions.shift());
    }
}

export const useSun = id => {
    document.querySelectorAll(`.player-suns .sun.sun-${id}`).forEach(sun => {
        sun.classList.remove(`sun-${id}`);
        sun.removeAttribute("data-type");
    });
}

export const replaceSun = (oldValue, newValue) => {
    const sunElement = document.querySelector(`.custom-player-area .sun.sun-${oldValue}`)
    
    sunElement.classList.remove(`sun-${oldValue}`);
    sunElement.classList.add(`sun-${newValue}`);
}

export const replaceAllPlayerSuns = (playerId, suns) => {
    const playerSuns = document.getElementById(`player_suns_${playerId}`);
    playerSuns.innerHTML = "";
    
    const playerBiddingSuns = document.querySelector(`#player-bidding-area-${playerId} .player-suns`);
    playerBiddingSuns.innerHTML = "";

    for (const sun of suns) {
        createSun(playerSuns, sun);
        createSun(playerBiddingSuns, sun);
    }
}

