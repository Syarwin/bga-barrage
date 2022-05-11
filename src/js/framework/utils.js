export const addActionButton = (id, label, callback, color = "blue") => {
    const html = `
        <div id="${id}" class="bgabutton bgabutton_${color}">${label}</div>
    `;

    document.getElementById("generalactions").insertAdjacentHTML("beforeend", html);

    const newButton = document.getElementById(id);
    newButton.addEventListener("click", callback);

    return newButton;

}

let gamegui = null;
export let sendAction = () => {};

export const initUtils = ggui => {
    gamegui = ggui;
    sendAction = (action, args = {}, lock = true) => {
        gamegui.ajaxcall(`/${gamegui.game_name}/${gamegui.game_name}/${action}.html`, {
            lock, ...args
        }, gamegui, () => {}, () => {});    
    };
}

export const getActivePlayerId= () => {
    return gamegui.getActivePlayerId();
}

export const isCurrentPlayerActive = () => {
    return gamegui.isCurrentPlayerActive();
}

export const ifActivePlayer = (callback) => {
    return (...args) => {
        if (isCurrentPlayerActive()) {
            return callback(...args);
        }    
    }
}

export const ifAnyPlayer = (callback) => {
    return (...args) => {
        return callback(...args);
    }
}

export const chainCallbacks = (...callbacks) => {
    return (...args) => {
        let context = null;
        for (const cb of callbacks) {
            context = cb(...args, context);
        }

        return context;
    }
}

export const getCurrentPlayerId = () => {
    return gamegui.player_id;
}

export const isCurrentPlayer = (playerId) => {
    return gamegui.player_id == playerId;
}

export const getRandomNumberBetween = (min, max) => Math.floor(Math.random() * (max - min + 1) + min);

export const checkIfActionPossible = (action, callback) => {
    return (...args) => {
        if (gamegui.checkAction(action)) {
            callback(...args);
        }
    }
}

export const showDialog = (id, title, html, maxWidth, hideCloseIcon = true ) => {
    const dialog = new ebg.popindialog();
    dialog.create(id);
    dialog.setTitle(title);
    maxWidth && dialog.setMaxWidth(maxWidth);
    
    dialog.setContent(html);
    dialog.show();

    hideCloseIcon && dialog.hideCloseIcon();

    document.getElementById("popin_score-dialog_underlay").addEventListener("click", () => {
        dialog.destroy();
    });

    return dialog;
}

export const getPlayers = () => {
    return gamegui.gamedatas.players;
}

export const getPlayerName = playerId => {
    return getPlayers()[playerId].name;
}

export const getPlayerNameWithColor = playerId => {
    return `<span class="player-name" style="color: #${getPlayers()[playerId].color}">${getPlayerName(playerId)}</span>`;
}

export const arrayGroupBy = (arr, callback) => {
    return arr.reduce((acc, el) => {
        const groupName = callback(el);
        if (!acc.hasOwnProperty(groupName)) {
            acc[groupName] = [];
        }

        acc[groupName].push(el);
        return acc;
    }, {});
}

export const setPlayerScore = (playerId, score, animate = true) => {
    animate && gamegui.scoreCtrl[playerId].toValue(score);
    !animate && gamegui.scoreCtrl[playerId].setValue(score);    
}

export const increasePlayerScore = (playerId, delta, animate = true) => {
    const newScore = gamegui.scoreCtrl[playerId].getValue() + delta;

    setPlayerScore(playerId, newScore, animate);
}

export const disableScore = (playerId) => {
    gamegui.scoreCtrl[playerId].disable();
}

export const orderPlayersWithCurrentPlayerFirst = (original) => {
    const players = [...original];
    //sort players by order
    players.sort((a, b) => a.order - b.order);

    //find index of player with currentPlayerId
    const currentPlayerIndex = players.findIndex(player => +player.id === +getCurrentPlayerId())
    //move players in front of current player to the back
    const toMoveBack = players.splice(0, currentPlayerIndex);
    players.push(...toMoveBack);

    return players;
}

export const insertElement = (parent, template, tooltipTemplate, position="beforeend") => {
    parent.insertAdjacentHTML(position, template);
    if (tooltipTemplate) {
        let element = null;
        switch (position) {
            case "beforeend":
                element = parent.lastElementChild;
                break;
            case "afterbegin":
                element = parent.firstElementChild;
                break;
            case "beforebegin":
                element = parent.previousElementSibling;
                break;
            case "afterend":
                element = parent.nextElementSibling;
            default:
                break;
        }

        if (element) {
            gamegui.addTooltipHtml(element.id, tooltipTemplate);
        }
    }
}

