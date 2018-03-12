module.exports = function( grunt ) {

	'use strict';
	var banner = '/**\n * <%= pkg.homepage %>\n * Copyright (c) <%= grunt.template.today("yyyy") %>\n * This file is generated automatically. Do not edit.\n */\n';
	// Project configuration
	grunt.initConfig( {

		pkg: grunt.file.readJSON( 'package.json' ),

		addtextdomain: {
			options: {
				textdomain: 'platform-shell-plugin',
			},
			update_all_domains: {
				options: {
					updateDomains: true
				},
				src: [ '*.php', '**/*.php', '!node_modules/**', '!php-tests/**', '!bin/**' ]
			}
		},

		wp_readme_to_markdown: {
			your_target: {
				files: {
					'README.md': 'readme.txt'
				}
			},
		},

		makepot: {
			target: {
				options: {
					domainPath: '/languages',
					mainFile: 'platform-shell-plugin',
					potFilename: 'platform-shell-plugin-fr_CA.pot',
					exclude: ['src/lib/plugin-update-checker-4.4/.*'],
					potHeaders: {
                                            'Last-Translator': '\n',
                                            'Language-Team': '\n',
                                            'x-poedit-keywordslist': true,
                                            'language': 'fr_CA',
                                            'plural-forms': 'nplurals=2; plural=(n != 1);',
                                            'x-poedit-country': 'Canada',
                                            'x-poedit-sourcecharset': 'UTF-8',
                                            'x-poedit-basepath': '../',
                                            'x-poedit-searchpath-0': '.',
                                            'x-poedit-bookmarks': '',
                                            'x-textdomain-support': 'yes',
                                            'Report-Msgid-Bugs-To' : ''
					},
					type: 'wp-plugin',
					updateTimestamp: true,
                                        updatePoFiles: false /* bug msgmerge, utiliser outils catalogue poedit. */
				}
			}
		}
	} );

	grunt.loadNpmTasks( 'grunt-wp-i18n' );
	grunt.loadNpmTasks( 'grunt-wp-readme-to-markdown' );
	grunt.registerTask( 'i18n', ['addtextdomain', 'makepot'] );
	
    // Ne pas utiliser cette tâche. Le readme.txt style WordPress
    // est utilisé seulement pour gérer la mise à jour du plugin avec plugin update checker.
    // Le Readme.md est un fichier complètement différent (github).
    //grunt.registerTask( 'readme', ['wp_readme_to_markdown'] );

	grunt.util.linefeed = '\n';

};
