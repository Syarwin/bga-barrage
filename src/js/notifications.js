import { disableBiddingLayout, moveRaBack, resetCurrentBid, setCurrentBid } from './bidding';
import { addRa, addTile, decreaseRemainingTiles, getLastRaTile, resetRaTrack } from './board';
import { cloneSlideToAndDestroy, moveOnTopAnElement, slideFrom } from './framework/animation';
import { attach, autodetach } from './framework/event';
import { addActionButton, arrayGroupBy, increasePlayerScore, isCurrentPlayer, setPlayerScore } from './framework/utils';
import { replaceAllPlayerSuns, replaceSun, useSun } from './players';
import { addPlayerTile } from './playerTiles';
import { showEpochScoreDialog, setCurrentEpochScore, showThirdEpochScoreDialog } from './scoring';

export const notifications = {
    tileDrawn: args => {
        addTile(args.position, args.tileId, args.tileType, args.tileTypeArg);
        const tile = document.getElementById(`offer_tile_${args.position}`);
        slideFrom(tile, document.getElementById("bag"));
        decreaseRemainingTiles();
    },
    raDrawn: () => {
        addRa();
        const tile = getLastRaTile();
        slideFrom(tile, document.getElementById("bag"));
        decreaseRemainingTiles();
    },
    bid: args => {
        setCurrentBid(args.sunValue);
    },
    auctionWon: args => {
        const winnerPanelElement = document.getElementById(`custom_player_area_${args.winnerId}`);
        document.querySelectorAll("#offer_tiles .tile").forEach(tile => {
            cloneSlideToAndDestroy(tile, winnerPanelElement, { top: -70 });
        });
        Object.values(args.allTiles).forEach(tile => {
            tile.location_arg = args.winnerId;
            addPlayerTile(tile);
        });
    },
    tilesDiscarded: args => {
        document.querySelectorAll("#offer_tiles .tile").forEach(tile => {
            cloneSlideToAndDestroy(tile, document.getElementById('main_play_area'), { top: 100, left: -1000 });
        });
    },
    sunReplaced: args => {
        const boardSunElement = document.getElementById("board_sun");
        const winningSunElement = document.querySelector(`.player-bidding-area .sun-${args.sunLost}`);

        resetCurrentBid();

        const boardSunClone = boardSunElement.cloneNode(true);
        const winningSunClone = winningSunElement.cloneNode(true);

        boardSunElement.classList.add("invisible");
        winningSunElement.classList.add("invisible");

        winningSunElement.parentElement.insertBefore(boardSunClone, winningSunElement);
        boardSunElement.parentElement.insertBefore(winningSunClone, boardSunElement);

        winningSunClone.classList.add("animated");
        boardSunClone.classList.add("animated");
        winningSunClone.classList.add("notransition");
        boardSunClone.classList.add("notransition");

        boardSunClone.removeAttribute("id");
        winningSunClone.id = "board_sun";

        moveOnTopAnElement(boardSunClone, boardSunElement, {}, "px");
        moveOnTopAnElement(winningSunClone, winningSunElement, {}, "px");

        setTimeout(() => {
            boardSunClone.classList.remove("notransition");
            winningSunClone.classList.remove("notransition");

            moveOnTopAnElement(boardSunClone, winningSunElement, {}, "px");
            moveOnTopAnElement(winningSunClone, boardSunElement, {}, "px");

            attach(boardSunClone, autodetach(() => {
                boardSunClone.classList.remove("animated");
                winningSunElement.remove();
                
                boardSunClone.style.top = null;
                boardSunClone.style.left = null;

                replaceSun(args.sunLost, args.sunWon);
                useSun(args.sunWon);
            }), "transitionend");

            attach(winningSunClone, autodetach(() => {
                winningSunClone.classList.remove("animated");
                boardSunElement.remove();
                
                winningSunClone.style.top = null;
                winningSunClone.style.left = null;
            }), "transitionend");
        }, 0);        
    },
    tilePicked: args => {
        const tileElement = document.querySelector(".player-tiles .tile[data-id='" + args.godTileId + "']");
        cloneSlideToAndDestroy(tileElement, document.getElementById('main_play_area'), { top: 100, left: -1000 })
        
        const activePlayerPanel = document.getElementById(`custom_player_area_${args.playerId}`);
        const offerTileElement = document.querySelector("#offer_tiles .tile[data-id='" + args.tile.id + "']");
        cloneSlideToAndDestroy(offerTileElement, activePlayerPanel, { top: -70 });
        
        args.tile.location_arg = args.playerId;
        addPlayerTile(args.tile);
    },
    raDiscarded: () => {
        document.querySelectorAll("#ra_track .tile").forEach(tile => {
            cloneSlideToAndDestroy(tile, document.getElementById('main_play_area'), { top: 100, left: -1000 });
        })
        resetRaTrack();
    },
    offerDiscarded: () => {
        document.querySelectorAll("#offer_tiles .tile").forEach(tile => {
            cloneSlideToAndDestroy(tile, document.getElementById('main_play_area'), { top: 100, left: -1000 });
        })
    },
    playerTilesDiscarded: args => {
        args.tileIds.forEach(tileId => {
            const tile = document.querySelector(".player-tiles .tile[data-id='" + tileId + "']");

            tile && cloneSlideToAndDestroy(tile, document.getElementById('main_play_area'), { top: 100, left: -1000 });
        });
    },
    resolveDisaster: args => {
        Object.values(args.allTiles).forEach(tile => {
            const tileElement = document.querySelector(".player-tiles .tile[data-id='" + tile.id + "']");

            tileElement && cloneSlideToAndDestroy(tileElement, document.getElementById('main_play_area'), { top: 100, left: -1000 });
        });
    },
    sunsFlipped: args => {
        const playerSuns = arrayGroupBy(Object.values(args.suns), sun => sun.location_arg);
        for (const playerId in playerSuns) {
            if (playerSuns.hasOwnProperty(playerId)) {
                const suns = playerSuns[playerId].map(sun => sun.id);
                replaceAllPlayerSuns(playerId, suns);
            }
        }
    },
    epochScoring: args => {
        setCurrentEpochScore(args.scores);
        showEpochScoreDialog(args.scores);
    },
    thirdEpochScoring: args => {
        setCurrentEpochScore(args.scores);
        showThirdEpochScoreDialog(args.scores);
    },
    playerScored: args => {
        if (isCurrentPlayer(args.playerId)) {
            increasePlayerScore(args.playerId, args.score);
        }
    },
    scoresRevealed: args => {
        for (const playerId in args.scores) {
            if (args.scores.hasOwnProperty(playerId)) {
                const value = args.scores[playerId];

                setPlayerScore(playerId, 0, false);
                increasePlayerScore(playerId, value.player_score);
            }
        }
    }
}

notifications.auctionWon.setSynchronous = 700;
notifications.sunReplaced.setSynchronous = 700;
notifications.raDrawn.setSynchronous = 1300;
notifications.tilesDiscarded.setSynchronous = 1300;
notifications.raDiscarded.setSynchronous = 1500;
notifications.offerDiscarded.setSynchronous = 700;