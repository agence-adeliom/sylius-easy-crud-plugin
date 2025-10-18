var Encore = require('@symfony/webpack-encore');

Encore
  .setOutputPath('./public/')
  .setPublicPath('/bundles/syliuseasycrudplugin/')
  .setManifestKeyPrefix('')

  .cleanupOutputBeforeBuild()
  .enableSourceMaps(!Encore.isProduction())
  .enableVersioning(Encore.isProduction())
  .disableSingleRuntimeChunk()

  .copyFiles({
    from: './assets/admin/iconpicker',
    to: 'iconpicker/[path][name].[ext]',
  })

  .addEntry('form-type-collection-sortable', './assets/admin/js/form-type-collection-sortable.js')
  .addEntry('form-type-collection', './assets/admin/js/form-type-collection.js')
  .addEntry('field-code-editor', './assets/admin/js/field-code-editor.js')
  .addEntry('field-slug', './assets/admin/js/field-slug.js')
  .addEntry('field-image', './assets/admin/js/field-image.js')
  .addEntry('text-editor', './assets/admin/js/text-editor.js')
;

module.exports = Encore.getWebpackConfig();
