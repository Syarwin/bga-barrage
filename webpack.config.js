const path = require('path');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const WebpackAutoUpload = require("./webpackAutoUpload");
const sftpConfig = require('./sftp.config.js');

module.exports = {
    entry: {
        barrage: './js/framework/index.js'
    },
    mode: 'development',
    devtool: "source-map",
    output: {
        path: path.resolve(__dirname, '../modules'),
        filename: '[name].bundle.js',
        library: {
            type: "amd",
            export: "default"
        }
    },
    module: {
        rules: [
          {
            test: /\.s[ac]ss$/i,
            use: [
                MiniCssExtractPlugin.loader,
                {
                    loader: "css-loader",
                    options: {
                        url: false
                    }
                },
                "sass-loader",
            ]
          },
        ],
    },
    plugins: [
        new MiniCssExtractPlugin({
          // Options similar to the same options in webpackOptions.output
          // both options are optional
          filename: "[name].bundle.css",
          chunkFilename: "[id].css",
        }),
        new WebpackAutoUpload(sftpConfig)
      ],
    watch: true,
    watchOptions: {
        ignored: "**/node_modules"
    }
};
