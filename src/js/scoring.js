import { ready } from "./actions";
import { addActionButton, showDialog } from "./framework/utils"
import { epochScoreDialog } from "./templates";

let currentEpochScore = null;

export const showEpochScoreDialog = (score) => {
    const dialog = showDialog("score-dialog", _("Epoch score"), epochScoreDialog(score));
    document.getElementById("readyScoreDialogButton").addEventListener("click", () => {
        ready();
        dialog.destroy();
    });
    document.getElementById("hideScoreDialogButton").addEventListener("click", () => {
        dialog.destroy();
    });
}

export const showThirdEpochScoreDialog = (score) => {
    const dialog = showDialog("score-dialog", _("Third epoch and end game score"), epochScoreDialog(score, true));
    document.getElementById("hideScoreDialogButton").addEventListener("click", () => {
        dialog.destroy();
    });
}

export const setCurrentEpochScore = (score) => {
    currentEpochScore = score;
}

export const getCurrentEpochScore = () => {
    return currentEpochScore;
}
