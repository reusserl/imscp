/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by Laurent Declercq
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

'use strict';

module.exports = function (grunt) {
	// Load grunt tasks automatically
	require('load-grunt-tasks')(grunt);

	// Time how long tasks take. Can help when optimizing build times
	require('time-grunt')(grunt);

	// Initialize configuration for all tasks
	grunt.initConfig({
		// Paths
		themePaths: {
			src: '.',
			dist: 'dist'
		},

		// Remove previous distribution files to start in fresh environment
		clean: {
			dist: {
				files: [
					{
						dot: true,
						src: [
							'.tmp',
							'<%= themePaths.dist %>/*'
						]
					}
				]
			}
		},

		// Collect all angular partial views into a single partial.js file
		//html2js: {
		//	options: {
		//		base: '<%= themePaths.src %>/angular'
		//	},
		//	angular: {
		//		src: ['<%= themePaths.src %>/angular/**/partial/*.html'],
		//		dest: '<%= themePaths.src %>/angular/partials.js'
		//	}
		//},

		// Copies remaining files to places other tasks can use
		copy: {
			dist: {
				options: {
					mode: true, // Copy the existing file and directories permissions.
					timestamp: true // Preserve the timestamp attributes(atime and mtime) when copying files
				},
				files: [
					{
						expand: true, // Enable dynamic expansion.
						dot: false, // Do not allow patterns to match filenames starting with a period
						flatten: true, // Remove all path part from generated dest paths.
						cwd: '<%= themePaths.src %>/shared/layouts', // All src matches are relative to (but don't include) this path
						dest: '<%= themePaths.dist %>/', // estination path prefix.
						src: ['*.tpl'] // Actual pattern(s) to match.
					}
				]
			}
		},

		// Reads HTML for usemin blocks to enable smart builds that automatically concat, minify and revision files.
		// Creates configurations in memory so additional tasks can operate on them
		useminPrepare: {
			html: '<%= themePaths.dist %>/*.tpl',
			options: {
				dest: '<%= themePaths.src %>',
				root: '<%= themePaths.src %>',
				staging: '.tmp',
				flow: {
					html: {
						steps: {
							js: ['concat', 'uglifyjs'],
							css: ['cssmin']
						},
						post: {}
					}
				}
			}
		},
		cssmin: {
			options: {
				keepSpecialComments: '0',
				root: '<%= themePaths.src %>',
				rebase: true // Rebase URL in asset files
			}
		},

		// Performs rewrites based on rev and the useminPrepare configuration
		usemin: {
			html: ['<%= themePaths.dist %>/*.tpl'],
			css: ['<%= themePaths.dist %>/{,**/}*.css'],
			options: {
				assetsDirs: ['<%= themePaths.src %>']
			}
		},

		// Create file revisions
		filerev: {
			dist: {
				src: '<%= themePaths.dist %>/{,**/}*.{css,js}'
			}
		},

		// Rebase include path in ui.tpl template
		replace: {
			dist: {
				options: {
					patterns: [
						{
							match: /(<!--\sINCLUDE\s")(\.\.\/)/g,
							replacement: '$1$2shared/'
						}
					]
				},
				files: [
					{
						expand: true,
						flatten: true,
						src: ['<%= themePaths.dist %>/ui.tpl'],
						dest: '<%= themePaths.dist %>'
					}
				]
			}
		}
	});

	grunt.registerTask('build', [
		'clean:dist', // Remove current distribution files
		//'html2js', // Minify angular partial view html
		'copy:dist', // Copy needed files
		'useminPrepare', // Prepare usemin tasks
		'concat', // Concatenate JS files
		'uglify', // Uglify JS files
		'cssmin', // Minify CSS
		'filerev:dist', // Create asset file revisions
		'replace:dist', // Update include paths in ui.tpl file
		'usemin' // Process work according useminPrepare task
	]);

	grunt.registerTask('default', [
		'build'
	]);
};
