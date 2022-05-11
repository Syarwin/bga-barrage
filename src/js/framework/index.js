import {initState, onEnteringState, onLeavingState, onUpdateActionButtons} from '../state';
import '../../styles/framework/main.scss';
import { addPolyfills, initUtils } from './utils';
import { logOverride } from '../logs';
import { notifications } from '../notifications';

export default {
    init: (state, gamegui) => {
        initUtils(gamegui);
        initState(state)
    },
    onEnteringState,
    onLeavingState,
    onUpdateActionButtons,
    logOverride,
    notifications
};