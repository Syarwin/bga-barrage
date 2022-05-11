import { drawTile, callRa, pass, spendGodTile, cancel, doneResolveDisaster, ready, skip } from './actions';
import * as board from './board';
import * as bidding from './bidding';
import { initPlayers } from './players';
import { addActionButton, chainCallbacks, getActivePlayerId, getCurrentPlayerId, ifActivePlayer, ifAnyPlayer, isCurrentPlayerActive } from './framework/utils';
import * as playerTiles from './playerTiles';
import { showEpochScoreDialog, setCurrentEpochScore, getCurrentEpochScore, showThirdEpochScoreDialog } from './scoring';

export const initState = (data) => {
    board.updateSun(data.boardSun);
    board.initializeRaTrack(data.raTilesCount, data.raTilesMax);
    board.initializeTileOffer(data.tileOffer);
    board.setRemainingTiles(data.remainingTiles);
    playerTiles.setupPlayerTiles(data.playerTiles, data.players);

    initPlayers(Object.values(data.players), getCurrentPlayerId());

    bidding.initBidding(data.currentBid, data.auctioneerId);

    if (data.score) {
        setCurrentEpochScore(data.score);

        if (isCurrentPlayerActive()) {
            showEpochScoreDialog(data.score);
        }
    }

    //todo remove
    document.getElementById(`temp_button`).addEventListener('click', () => {
        bidding.toggleBiddingLayout();
    }); 
}

export const onEnteringState = {
    playerTurn: chainCallbacks(
        ifAnyPlayer(() => {
            // bidding.disableBiddingLayout();
            // bidding.moveRaBack();
        }),
        ifActivePlayer(args => {
            if (args.canDrawTile) {
                addActionButton("drawTileButton", _("Draw tile"), drawTile);
            }
            board.activateBag();

            if (args.canSpendGodTile) {
                addActionButton("spendGoldTile", _("Spend God tile"), spendGodTile);
            }
            playerTiles.activateGodTiles();

            addActionButton("callRaButton", _("Call Ra"), callRa);
            bidding.activateRaFigure();
        })
    ),
    playerBidding: args => {
        bidding.enableBiddingLayout();
        bidding.setAuctioneer(args.auctioneerId);
        bidding.setActiveBidder(getActivePlayerId())
        setTimeout(() => {
            // Timeout needed as the figure is positioned according to the position of 
            // auctioneer bidding area. It is animated so we need it to settle on its
            // final position before we move the figure. 
            bidding.moveRaFigure();
        }, 500);
        
        if (isCurrentPlayerActive()){
            addActionButton("passButton", _("Pass"), pass);
            bidding.activateSuns();
        }
    },
    spendGodTile: ifActivePlayer(args => {
        addActionButton("cancelButton", _("Cancel"), cancel);
        board.activateOffer();
    }),
    spendMoreGodTile: ifActivePlayer(args => {
        addActionButton("skipButton", _("Skip"), skip);
        board.activateOffer();
    }),
    resolveDisaster: ifActivePlayer(args => {
        const button = addActionButton("doneButton", _("Done"), doneResolveDisaster);
        args.numberOfTilesToSelect > 0 && button.classList.add("disabled");
        
        playerTiles.activateTilesOfType(args.type, e => {
            e.target.classList.toggle("selected");

            const selectedTilesNumber = document.querySelectorAll(`#player_tiles_${getCurrentPlayerId()} .tile.selected`).length;
            if (selectedTilesNumber === args.numberOfTilesToSelect) {
                button.classList.remove("disabled");
            } else {
                button.classList.add("disabled");
            }

        }, "resolveDisaster");
    }),
    finalizeAuction: () => {
        bidding.disableBiddingLayout();
        bidding.moveRaBack();
    },
    gameEnd: () => {
        addActionButton("reviewScoreButton", _("Show score"), () => {
            showThirdEpochScoreDialog(getCurrentEpochScore());
        });
    }
}
export const onLeavingState = {
    playerTurn: () => {
        board.deactivateBag();
        bidding.deactivateRaFigure();
        playerTiles.deactivateGodTiles();
    },
    playerBidding: () => {
        bidding.deactivateSuns();
    },
    spendGodTile: () => {
        board.deactivateOffer();
    },
    resolveDisaster: () => {
        playerTiles.deactivateActiveTiles("resolveDisaster");
    }
}
export const onUpdateActionButtons = {
    reviewScore: ifActivePlayer(() => {
        addActionButton("readyButton", _("I'm ready for the next epoch"), ready)
        addActionButton("reviewScoreButton", _("Show score"), () => {
            showEpochScoreDialog(getCurrentEpochScore());
        });
    })
}