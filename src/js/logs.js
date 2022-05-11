import { sun, tile } from "./templates";

export const logOverride = {
    tileTypeDiv: (args) => {
        return tile(0, args.tileTypeDiv, args.tileTypeArg);
    },
    godTileTypeDiv: (args) => {
        return tile(0, args.godTileTypeDiv, args.godTileTypeArg);
    },
    sunValueDiv: (args) => {
        return sun(args.sunValueDiv);
    },
    sunWonDiv: (args) => {
        return sun(args.sunWonDiv);
    },
    sunLostDiv: (args) => {
        return sun(args.sunLostDiv);
    },
    minimumBidDiv: (args) => {
        return sun(args.minimumBidDiv);
    },
    allTilesDiv: (args) => {
        return Object.values(args.allTilesDiv).map((t)=>tile(0, t.type, t.type_arg)).join("");
    },
    disasterTileDiv: (args) => {
        return tile(0, "disaster", args.disasterTileDiv);
    }
};