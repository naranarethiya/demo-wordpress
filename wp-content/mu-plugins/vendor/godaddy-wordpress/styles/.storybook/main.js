const path = require( 'path' );

const modulesDir = path.resolve( process.cwd(), 'node_modules' );
console.debug( process.cwd() );

module.exports = {
  core: {
    builder: 'webpack5',
  },
  stories: [
    './**/*.stories.mdx',
    './**/*.stories.@(js|jsx|ts|tsx)',
  ],
  addons: [
    '@storybook/addon-links',
    '@storybook/addon-essentials',
    '@storybook/addon-interactions',
    '@storybook/addon-controls',
  ],
  webpackFinal: async ( config ) => {
    config.resolve.alias[ '@emotion/styled' ] = path.join( modulesDir, '@emotion/styled' );
    config.resolve.alias[ '@emotion/styled-base' ] = path.join( modulesDir, '@emotion/styled' );
    return config;
  },
}