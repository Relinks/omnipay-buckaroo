/** @type {import('semantic-release').GlobalConfig} */
const baseConfig = (await import('/semantic-release/config/release.config.js')).default;

const branches = ['feature/semrelease'];
const plugins = [
    '@semantic-release/release-notes-generator',
    [
        '@semantic-release/github',
        {
            'releaseNameTemplate': 'Release: <%= nextRelease.name %>',
            "successCommentCondition": false,
            "failComment": false,
            "prUpdate": false
        }
    ]
];

/**
 * @type {import('semantic-release').GlobalConfig}
 */
export default {
    branches: baseConfig.branches.concat(branches),
    plugins: baseConfig.plugins.concat(plugins)
};