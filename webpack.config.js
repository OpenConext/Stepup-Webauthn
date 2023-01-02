var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .addStyleEntry('global', './public/scss/application.scss')
    .addEntry('registration', './public/typescript/registration.tsx')
    .addEntry('authentication', './public/typescript/authentication.tsx')
    .enableSassLoader(function (options) {
        options.sassOptions = {
            outputStyle: 'expanded',
            includePaths: ['public'],
        };
    })
    .enableTypeScriptLoader()
    .cleanupOutputBeforeBuild()
    .enableSingleRuntimeChunk()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())
;

module.exports = Encore.getWebpackConfig();
