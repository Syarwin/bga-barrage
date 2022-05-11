const { validate } = require('schema-utils');
const { Client } = require('node-scp');
const path = require('path');

class WebpackAutoUpload {

    static defaultOptions = {
        port: 22
    };

    static schema = {
        type: 'object',
        properties: {
            host: {
                type: "string"
            },
            port: {
                type: "number"
            },
            username: {
                type: "string"
            },
            password: {
                type: "string"
            },
            remotePath: {
                type: "string"
            },
            privateKey: {
                type: "string"
            },
            passphrase: {
                type: "string"
            }
        },
        required: ["host", "port", "remotePath"],
        dependentRequired: {
            "username": ["password"],
            "privateKey": ["passphrase"],
        },
        additionalProperties: false
    };

	constructor(options = {}) {
		this.options = {...WebpackAutoUpload.defaultOptions, ...options};

        validate(WebpackAutoUpload.schema, this.options, {
            name: 'WebpackAutoUpload',
            baseDataPath: 'options',
        });
	}

    apply(compiler) {
        compiler.hooks.assetEmitted.tapPromise(
            'WebpackAutoUpload',
            async (file, { content, source, outputPath, compilation, targetPath }) => {
                console.log(`${file}: sft upload started...`);

                const client = await Client(this.options);
                await client.uploadFile(targetPath, path.join(this.options.remotePath, file));
                console.log(`${file}: sft upload finished!`);
                client.close();
            }
        );
    }
}
