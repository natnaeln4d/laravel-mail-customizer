const path = require('path');
const { VueLoaderPlugin } = require('vue-loader');

module.exports = {
    entry: './src/Resources/assets/js/mail-customizer.js',
    output: {
        path: path.resolve(__dirname, 'dist'),
        filename: 'mail-customizer.js',
        library: 'MailCustomizer',
        libraryTarget: 'umd',
        globalObject: 'this'
    },
    module: {
        rules: [
            {
                test: /\.vue$/,
                loader: 'vue-loader'
            },
            {
                test: /\.jsx?$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: [
                            ['@babel/preset-env', { targets: { browsers: ['> 1%', 'last 2 versions'] } }],
                            '@babel/preset-react'
                        ]
                    }
                }
            },
            {
                test: /\.css$/,
                use: ['vue-style-loader', 'css-loader']
            }
        ]
    },
    plugins: [
        new VueLoaderPlugin()
    ],
    resolve: {
        extensions: ['.js', '.jsx', '.vue'],
        alias: {
            'vue$': 'vue/dist/vue.esm.js'
        }
    },
    externals: {
        'vue': 'Vue',
        'react': 'React',
        'react-dom': 'ReactDOM',
        'axios': 'axios'
    }
};