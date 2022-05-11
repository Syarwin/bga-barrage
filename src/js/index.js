import {initState, onEnteringState, onLeavingState, onUpdateActionButtons} from './state';
import '../styles/style.scss';
import { setupAjaxCall } from './framework/sendAction';

export default {
    init: (state, gamegui) => {
        const currentPlayerId = gamegui.player_id;
        initState(state, currentPlayerId)
        setupAjaxCall(gamegui)

    },
    onEnteringState,
    onLeavingState,
    onUpdateActionButtons
};