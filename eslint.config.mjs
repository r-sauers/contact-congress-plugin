import path from "node:path";
import react from "eslint-plugin-react";
import { fileURLToPath } from "node:url";
import js from "@eslint/js";
import { FlatCompat } from "@eslint/eslintrc";

const __filename = fileURLToPath( import.meta.url );
const __dirname = path.dirname( __filename );
const compat = new FlatCompat({
    baseDirectory: __dirname,
    resolvePluginsRelativeTo: __dirname,
    recommendedConfig: js.configs.recommended,
    allConfig: js.configs.all
});

export default [
    {
        files: [ "**/*.jsx" ],
        plugins: {react}
    },
    {
        ignores: [ "**/vendor/", "**/node_modules/" ]
    },
    ...compat.extends( "plugin:react/recommended", "wordpress" ),
    {
        rules: {
            "no-console": "warn",
            quotes: [ "error", "double" ]
        }
    }
];
