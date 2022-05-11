export let sendAction = () => {};

export const setupAjaxCall = (gamegui) => {
    sendAction = (action, args = {}, lock = true) => {
        gamegui.ajaxcall(`/${gamegui.game_name}/${gamegui.game_name}/${action}.html`, {
            lock, ...args
        }, gamegui, () => {}, () => {});    
    };
}
