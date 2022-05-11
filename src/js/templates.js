import { getPlayerNameWithColor, getRandomNumberBetween } from "./framework/utils";

export const playerPanel = player => (`
    <div id="custom_player_area_${player.id}" class="custom-player-area">
        <div id="player_suns_${player.id}" class="player-suns"></div>
    </div>
`);

export const sun = (type, rotated) => {
    let additionalClass = "";

    if (type !== null) {
        additionalClass = `sun-${type}`;
    }

    if (rotated) {
        additionalClass += ` rotated-${rotated}`;
    }

    return `<div class="sun ${additionalClass}" data-type="${type}"></div>`
};

export const playerBiddingArea = (player, position) => (`
    <div id="player-bidding-area-${player.id}" class="player-bidding-area position-${position}">
        <div class="mover box">
            <div class="player-name" style="color: #${player.color}">${player.name}</div>
            <div class="player-suns"></div>
        </div>
    </div>
`);

const offerTileId = id => `offer_tile_${id}`;
const raTileId = id => `ra_tile_${id}`;

export const offerTile = (ordinal, id, type, typeArg) => {
    return tile(id, type, typeArg, offerTileId(ordinal));
}

export const raTile = (ordinal, id, type, typeArg) => {
    return tile(id, type, typeArg, raTileId(ordinal));
}

export const tile = (id, type, typeArg, elementId) => {
    let className = `tile-${type}`;

    if (typeArg != 0 && typeArg != undefined) {
        className += `-${typeArg}`;
    }

    let idAttr = "";
    if (elementId) {
        idAttr = `id="${elementId}"`;
    }

    const rotation = getRandomNumberBetween(-2, 2) + "deg";

    return `<div ${idAttr} class="tile ${className}" data-id="${id}" style="--random-rotation:${rotation}"></div>`
};

export const playerTiles = ({id, name, color}) => (`
<div id="player_tiles_${id}" class="player-tiles box">
    <div class="player-tiles-content">
        <div class="main">
            <div class="player-name" style="color:#${color}">${name}</div>
        </div>
        <div class="monument tile-category"></div>
        <div class="pharaoh tile-category"></div>
        <div class="river tile-category"></div>
        <div class="god tile-category"></div>
        <div class="gold tile-category"></div>
        <div class="civilization tile-category"></div>
        <div class="flood tile-category"></div>
    </div>
</div>
`);

const createGodHeader = () => (`
<th>
    ${tile(null, "god", 1)}
</th>
<th class="scoring-description">
    <div>
        <div>${_("Each: 2 points")}</div>
    </div>
</th>
`)

const createGoldHeader = () => (`
<th>
    ${tile(null, "gold")}
</th>
<th class="scoring-description">
    <div>
        <div>${_("Each: 3 points")}</div>
    </div>
</th>
`)

const createCivilizationHeader = () => (`
<th>
    ${tile(null, "civilization", 1)}
</th>
<th class="scoring-description">
    <div>
        <div>${_("None: -5 points")}</div>
        <div>${_("3 types: 5 points")}</div>
        <div>${_("4 types: 10 points")}</div>
        <div>${_("5 types: 15 points")}</div>
    </div>
</th>
`)

const createRiverHeader = () => (`
<th>
    ${tile(null, "river", 1)}
</th>
<th class="scoring-description">
    <div>
        <div>${_("Each: 1 point")}</div>
        <div>${_("Must have at lease one flood tile")}</div>
    </div>
</th>
`)

const createPharaohHeader = () => (`
<th>
    ${tile(null, "pharaoh")}
</th>
<th class="scoring-description">
    <div>
        <div>${_("Most: 5 points")}</div>
        <div>${_("Least: -2 points ")}</div>
    </div>
</th>
`)
const createDifferentMonumentHeader = () => (`
<th>
    ${tile(null, "monument", 1)}
</th>
<th class="scoring-description">
    <div>
        <div>${_("8 types: 15 points")}</div>
        <div>${_("7 types: 10 points")}</div>
        <div>${_("<7 types: 1 point per type")}</div>
    </div>
</th>
`)
const createIdenticalMonumentHeader = () => (`
<th>
    ${tile(null, "monument", 2)}
</th>
<th class="scoring-description">
    <div>
        <div>${_("3 identical: 5 points")}</div>
        <div>${_("4 identical: 10 points")}</div>
        <div>${_("5 identical: 15 points")}</div>
    </div>
</th>
`)
const createSunHeader = () => (`
<th>
    ${sun(1, false)}
</th>
<th class="scoring-description">
    <div>
        <div>${_("Most: 5 points")}</div>
        <div>${_("Least: -5 points ")}</div>
    </div>
</th>
`)

const createGodScoringRow = scores => (`${createGodHeader()}${scores.join("")}`);
const createGoldScoringRow = scores => (`${createGoldHeader()}${scores.join("")}`);
const createCivilizationScoringRow = scores => (`${createCivilizationHeader()}${scores.join("")}`);
const createRiverScoringRow = scores => (`${createRiverHeader()}${scores.join("")}`);
const createPharaohScoringRow = scores => (`${createPharaohHeader()}${scores.join("")}`);
const createDifferentMonumentScoringRow = scores => (`${createDifferentMonumentHeader()}${scores.join("")}`);
const createIdenticalMonumentScoringRow = scores => (`${createIdenticalMonumentHeader()}${scores.join("")}`);
const createSunScoringRow = scores => (`${createSunHeader()}${scores.join("")}`);

export const epochScoreDialog = (score, addEndGameScoring = false) => {
    const headers = [];
    const godScores = [];
    const goldScores = [];
    const civilizationScores = [];
    const riverScores = [];
    const pharaohScores = [];
    const differentMonumentScores = [];
    const identicalMonumentScores = [];
    const sunScores = [];
    const totalScores = [];

    for (const playerId in score) {
        if (score.hasOwnProperty(playerId)) {
            const playerScore = score[playerId];
            
            headers.push(`<th>${getPlayerNameWithColor(playerId)}</th>`);
            godScores.push(`<td>${playerScore.points.god}</td>`);
            goldScores.push(`<td>${playerScore.points.gold}</td>`);
            civilizationScores.push(`<td>${playerScore.points.civilization}</td>`);
            riverScores.push(`<td>${playerScore.points.river}</td>`);
            pharaohScores.push(`<td>${playerScore.points.pharaoh}</td>`);
            differentMonumentScores.push(`<td>${playerScore.points.differentMonument}</td>`);
            identicalMonumentScores.push(`<td>${playerScore.points.identicalMonument}</td>`);
            sunScores.push(`<td>${playerScore.points.sun}</td>`);
            totalScores.push(`<td>${playerScore.points.total}</td>`);
        }
    }

    const header = `<td colspan="2"></td>${headers.join("")}`;
    const totals = `<th colspan="2">${_("Total")}</th>${totalScores.join("")}`;

    return `
        <table class="score-dialog">
            <thead>
                <tr>
                    ${header}
                </tr>
            </thead>
            <tbody>
                <tr>${createGodScoringRow(godScores)}</tr>
                <tr>${createGoldScoringRow(goldScores)}</tr>
                <tr>${createCivilizationScoringRow(civilizationScores)}</tr>
                <tr>${createRiverScoringRow(riverScores)}</tr>
                <tr>${createPharaohScoringRow(pharaohScores)}</tr>
                ${addEndGameScoring ? `<tr><th class="end_game_score_title" colspan="7">${_("End game scoring")}</th>` : ""}
                ${addEndGameScoring ? `<tr>${createDifferentMonumentScoringRow(differentMonumentScores)}</tr>` : ""}
                ${addEndGameScoring ? `<tr>${createIdenticalMonumentScoringRow(identicalMonumentScores)}</tr>` : ""}
                ${addEndGameScoring ? `<tr>${createSunScoringRow(sunScores)}</tr>` : ""}
            </tbody>
            <tfoot>
                <tr>${totals}</tr>
            </tfoot>
        </table>
        ${!addEndGameScoring ? `<div id="readyScoreDialogButton" class="bgabutton bgabutton_blue">${_("I'm ready for the next epoch")}</div>` : ""}
        <div id="hideScoreDialogButton" class="bgabutton bgabutton_red">${_("Hide scores")}</div>
    `;
};

const tooltipInfo = (title, description) => (`
    <div>${title}</div>
    <div>${description}</div>
`)

export const tileTooltipInfo = (type, arg) => {
    const info = {
        civilization: tooltipInfo(_("Civilization"), _("For having none at the end of the epoch you would loose 5 poonts"))
    }[type];

    if (!info) {
        return `${type} ${arg}`
    }

    return info;
}