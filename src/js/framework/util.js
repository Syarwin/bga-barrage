export const addActionButton = (id, label, callback, color = "blue") => {
    const html = `
        <div id="${id}" class="bgabutton bgabutton_${color}">${label}</div>
    `;

    document.getElementById("generalactions").insertAdjacentHTML("beforeend", html);

    document.getElementById(id).addEventListener("click", callback);

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

export const isCurrentPlayerActive = () => {
    return gamegui.isCurrentPlayerActive();
}