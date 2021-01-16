/**
 * Grunt tasks configuration file.
 *
 * @link http://gruntjs.com/
 *
 * jshint esversion: 6
 */

module.exports = (grunt) => {
    grunt.initConfig({

        /**
         * Package definitions.
         */
        pkg: grunt.file.readJSON("package.json"),


        /**
         * Banner to be printed to all processed files.
         */
        fileBanner:
            "/**\n" +
            " * <%= pkg.name %> v<%= pkg.version %> [<%= grunt.template.today(\"yyyy-mm-dd\") %>]\n" +
            " *\n" +
            " * <%= pkg.description %>\n" +
            " *\n" +
            " * Package    <%= pkg.package %>\n" +
            " * Subpackage <%= pkg.subPackage %>\n" +
            " * Version    <%= pkg.version %>\n" +
            " * Author     <%= pkg.author.name %> <<%= pkg.author.email %>>\n" +
            " */\n\n",


        /**
         * Script files validation.
         *
         * @link https://github.com/gruntjs/grunt-contrib-jshint
         */
        jshint: {
            files: ["gruntfile.js", "../scripts/**/*.js"],
            options: {
                jshintrc: true,
                globals: {
                    window: false,
                    document: false,
                    $: true,
                    jQuery: false
                },
                validthis: true
            }
        },


        /**
         * Script files validation/minification.
         *
         * @link https://github.com/gruntjs/grunt-contrib-uglify
         */
        uglify: {
            options: {
                mangle: false,
                sourceMap: true,
                sourceMapIncludeSources: true
            },

            public: {
                options: {
                    sourceMapName: "../min/site-scripts.js.map"
                },
                files: {
                    "../min/polyfills.js": [
                        "../scripts/polyfills/*.js"
                    ],
                    "../min/scripts.js": [
                        "../scripts/site-scripts.js"
                    ]
                }
            }
        },


        /**
         * Sass.
         *
         * @link https://www.npmjs.com/package/grunt-dart-sass
         */
        'dart-sass': {
            target: {
                options: {
                    sourceMap: true,
                    outputStyle: "compressed"
                },
                files: {
                    "../min/styles.css": "../scss/site-styles.scss"
                }
            }
        },


        /**
         * Add custom banner to processed files.
         *  - postion: "bottom", "top", "replace"
         *
         * @link https://github.com/mattstyles/grunt-banner
         */
        usebanner: {
            dist: {
                options: {
                    position: "replace",
                    replace: true,
                    banner: "<%= fileBanner %>" /* Defined above ^ */,
                    linebreak: true
                },
                files: {
                    src: ["../min/*.css", "../min/*.js"]
                }
            }
        },


        /**
         * Run tasks whenever watched files change.
         *
         * @link https://github.com/gruntjs/grunt-contrib-watch
         */
        watch: {
            options: {
                reload: true,
                spawn: false
            },

            grunt: {
                files: ["gruntfile.js"]
            },

            scss: {
                files: ["../scss/**/*.scss"],
                tasks: ["dart-sass", "usebanner"]
            },

            scripts: {
                files: ["../scripts/**/*.js"],
                tasks: ["jshint", "uglify", "usebanner"]
                // tasks: ["jshint", "usebanner"] // For debugging purposes.
            }
        }
    });


    /**
     * Load NPM task modules.
     */
    grunt.loadNpmTasks("grunt-banner");
    grunt.loadNpmTasks("grunt-contrib-jshint");
    grunt.loadNpmTasks("grunt-dart-sass");
    grunt.loadNpmTasks("grunt-contrib-uglify-es");
    grunt.loadNpmTasks("grunt-contrib-watch");
    grunt.loadNpmTasks("grunt-notify");


    /**
     * Register task(s).
     */
    grunt.registerTask("default", ["watch"]);
};

/* <> */
