'use strict'

var node_dir = __dirname + '/node_modules';

const path = require('path')
const webpack = require('webpack')
const autoprefixer = require('autoprefixer')
const HtmlWebpackPlugin = require('html-webpack-plugin');

module.exports = {
  mode: 'development',
  context: path.join(__dirname, '.'),
  resolve: {
    alias: {
      jquery: node_dir + '/jquery/dist/jquery',
      noUiSlider: node_dir + '/nouislider/dist/nouislider'
    }
  },
  entry: {
    main: './src/js/main.js'
  },
  devServer:{
    static: path.resolve(__dirname, 'dist'),
    port: 8080,
    hot: true
  },
  plugins: [
    new HtmlWebpackPlugin({ template: './src/index.html' }),
    new webpack.ProvidePlugin({
      $: "jquery",
      jQuery: "jquery"
    })
  ],
  module: {
    rules: [
      {
        test: /jquery/,
        use: [
          {
            loader: "imports-loader",
            options: {
              imports: {
                moduleName: "jquery",
                name: "$",
              },
              additionalCode:
                "var define = false; /* Disable AMD for misbehaving libraries */",
            }
          }
        ]
      },
      {
        test: /nouislider/,
        use: [
          {
            loader: "imports-loader",
            options: {
              imports: {
                moduleName: "noUiSlider",
                name: "noUiSlider"
              }
            },
          }
        ]
      },
      {
        test: /slider/,
        use: [
          {
            loader: 'imports-loader',
            options: {
              imports: {
                moduleName: 'nouislider',
                name: 'nouislider'
              }
            },
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
