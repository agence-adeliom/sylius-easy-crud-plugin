var Encore = require('@symfony/webpack-encore');

Encore
  .setOutputPath('./src/Resources/public/')
  .setPublicPath('/bundles/syliuseasycrudplugin/')
  .setManifestKeyPrefix('')

  .cleanupOutputBeforeBuild()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .disableSingleRuntimeChunk()

  .copyFiles({
    from: './assets/iconpicker',
    to: 'iconpicker/[path][name].[ext]',
  })

  .addEntry('form-type-collection-sortable', './assets/js/form-type-collection-sortable.js')
  .addEntry('field-code-editor', './assets/js/field-code-editor.js')
  .addEntry('field-slug', './assets/js/field-slug.js')
  .addEntry('field-image', './assets/js/field-image.js')
  .addEntry('form-nested-accordion', './assets/js/form-nested-accordion.js')
  .addEntry('text-editor', './assets/js/text-editor.js')
;

module.exports = Encore.getWebpackConfig();
