var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .addStyleEntry('global', './public/scss/application.scss')
    .addEntry('registration', './public/typescript/registration.ts')
    .enableSassLoader(function (options) {
        // https://github.com/sass/node-sass#options.
        options.includePaths = [
            'node_modules/bootstrap-sass/assets/stylesheets'
        ];
        options.outputStyle = 'expanded';
    })
    .enableTypeScriptLoader()
    .cleanupOutputBeforeBuild()
    .enableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())
;

module.exports = Encore.getWebpackConfig();
