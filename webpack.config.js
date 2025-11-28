'use strict'

var node_dir = __dirname + '/node_modules';

const path = require('path')
const webpack = require('webpack')
const autoprefixer = require('autoprefixer')

module.exports = {
  mode: 'development',
  context: path.join(__dirname, '.'),
  resolve: {
    alias: {
      jquery: node_dir + '/jquery/dist/jquery',
      noUiSlider: node_dir + '/nouislider/dist/nouislider',
      autocompleteUser: 'src/js/autocompleteUser',
      form: 'src/js/form.js'
    }
  },
  entry: {
    main: './src/js/main.js'
  },
  output: {
    path: path.resolve(__dirname + '/public/build'),
    filename: "bundle.js",
    clean: true
  },
  plugins: [
    new webpack.ProvidePlugin({
      $: "jquery",
      jQuery: "jquery"
    })
  ],
  module: {
//    noParse: /src[\\/]css[\\/]/,
    rules: [
      {
        test: /\.woff2?$/,
        type: "asset/resource",
      },
      {
        test: /.js$/,
        exclude: /node_modules/,
        loader: 'babel-loader',
      },
      {
        test: require.resolve('jquery'),
        use: [
          {
            loader: 'expose-loader',
            options: {
              exposes: ["$", "jQuery"],
            }
          }
        ]
      },
      {
        test: /\.(scss)$/,
        use: [
          {
            // Adds CSS to the DOM by injecting a `<style>` tag
            loader: 'style-loader'
          },
          {
            // Interprets `@import` and `url()` like `import/require()` and will resolve them
            loader: 'css-loader'
          },
          {
            // Loader for webpack to process CSS with PostCSS
            loader: 'postcss-loader',
            options: {
              postcssOptions: {
                plugins: [
                  autoprefixer
                ]
              }
            }
          },
          {
            // Loads a SASS/SCSS file and compiles it to CSS
            loader: 'sass-loader',
            options: {
              sassOptions: {
                // Silence Sass deprecation warnings
                silenceDeprecations: [
                  'mixed-decls',
                  'color-functions',
                  'global-builtin',
                  'import'
                ]
              }
            }
          }
        ],

      }
    ]
  },
  ignoreWarnings: [
    {
      file: /\styles\.scss$/,
      message: /your warning message that need to be suppressed/,
    },
    (warning, compilation) => true
  ]
}
