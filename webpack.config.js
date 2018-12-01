const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const path = require('path');


module.exports = {
    entry: {
        'admin': './assets/js/admin.js',
        'admin.blocks.create': './assets/js/admin.blocks.create.js',
        'admin.menus.edit': './assets/js/admin.menus.edit.js',
        'admin.pages.edit': './assets/js/admin.pages.edit.js',
        'admin.sliders.edit': './assets/js/admin.sliders.edit.js',
        'admin.widget.accordion': './assets/js/admin.widget.accordion.js',
        'admin.widget.tabs': './assets/js/admin.widget.tabs.js',
        'admin.widgeteditor': './assets/js/admin.widgeteditor.js'
    },
    output: {
        filename: '[name].min.js',
        path: path.resolve(__dirname, 'assets/js/')
    },
    module: {
        rules: [
            {
                test: /\.(css|scss|sass)$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    {
                        loader: 'css-loader',
                        options: {
                            minimize: true,
                            importLoaders: 2,
                            url: false
                        }
                    },
                    {
                        loader: 'postcss-loader',
                        options: {
                            plugins: () => [require('autoprefixer')]
                        }
                    },
                    'sass-loader'
                ]
            },
        ]
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: '../css/[name].min.css',
            allChunks: true
        }),
    ],
    mode: 'production'
};
