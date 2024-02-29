var Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/build/')
    .copyFiles({
        from: './assets/openconext/images',
        to: './images/[path][name].[ext]',
    })
    .setPublicPath('/build')
    .cleanupOutputBeforeBuild()
    .addStyleEntry('global', './assets/scss/application.scss')
    .enableStimulusBridge('./assets/controllers.json')
    .addEntry('app', './assets/app.js')
    .enableSassLoader(function (options) {
        options.sassOptions = {
            outputStyle: 'expanded',
            includePaths: ['public'],
        };
    })
    .enableTypeScriptLoader()
    .cleanupOutputBeforeBuild()
    .enableSingleRuntimeChunk()
    .enableSourceMaps()
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())
;

module.exports = Encore.getWebpackConfig();
