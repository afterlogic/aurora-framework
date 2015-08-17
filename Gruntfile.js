

/*jshint node: true */

'use strict';

module.exports = function(grunt) {
	
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		cfg: {
			releasesSrcPath: ''
		},
		less: {
			options: {
				optimization: 2,
				compress: false,
				yuicompress: false
			},
			Default: {
				files: {
					"skins/Default/styles.css": "skins/Default/less/styles.less",
					"skins/Default/styles-mobile.css": "skins/Default/less/styles-mobile.less"
				}
			},
			Netvision: {
				files: {
					"skins/Netvision/styles.css": "skins/Netvision/less/styles.less",
					"skins/Netvision/styles-mobile.css": "skins/Netvision/less/styles-mobile.less"
				}
			},
			White: {
				files: {
					"skins/White/styles.css": "skins/White/less/styles.less",
					"skins/White/styles-mobile.css": "skins/White/less/styles-mobile.less"
				}
			},
			Quickme: {
				files: {
					"skins/Quickme/styles.css": "skins/Quickme/less/styles.less",
					"skins/Quickme/styles-mobile.css": "skins/Quickme/less/styles-mobile.less"
				}
			},
			BlueJeans: {
				files: {
					"skins/BlueJeans/styles.css": "skins/BlueJeans/less/styles.less",
					"skins/BlueJeans/styles-mobile.css": "skins/BlueJeans/less/styles-mobile.less"
				}
			},
			Blue: {
				files: {
					"skins/Blue/styles.css": "skins/Blue/less/styles.less",
					"skins/Blue/styles-mobile.css": "skins/Blue/less/styles-mobile.less"
				}
			},
			DeepForest: {
				files: {
					"skins/DeepForest/styles.css": "skins/DeepForest/less/styles.less",
					"skins/DeepForest/styles-mobile.css": "skins/DeepForest/less/styles-mobile.less"
				}
			},
			Autumn: {
				files: {
					"skins/Autumn/styles.css": "skins/Autumn/less/styles.less",
					"skins/Autumn/styles-mobile.css": "skins/Autumn/less/styles-mobile.less"
				}
			},
			OpenWater: {
				files: {
					"skins/OpenWater/styles.css": "skins/OpenWater/less/styles.less",
					"skins/OpenWater/styles-mobile.css": "skins/OpenWater/less/styles-mobile.less"
				}
			},
			Ecloud: {
				files: {
					"skins/Ecloud/styles.css": "skins/Ecloud/less/styles.less",
					"skins/Ecloud/styles-mobile.css": "skins/Ecloud/less/styles-mobile.less"
				}
			},
			Funny: {
				files: {
					"skins/Funny/styles.css": "skins/Funny/less/styles.less",
					"skins/Funny/styles-mobile.css": "skins/Funny/less/styles-mobile.less"
				}
			}
		},
		concat: {
			css_libs: {
				nonull: true,
				src: [
					"dev/Styles/normalize/normalize.css",
					"dev/Vendors/jquery-ui-1.10.4.custom/css/smoothness/jquery-ui-1.10.4.custom.min.css",
					"dev/Vendors/fullcalendar-2.2.3/fullcalendar.min.css",
//					"dev/Vendors/fullcalendar-3.2.1/fullcalendar.min.css",
					"dev/Vendors/inputosaurus/inputosaurus.css"
				],
				dest: 'static/css/libs.css'
			}
		},
		cssmin: {
			css_libs: {
				src: 'static/css/libs.css',
				dest: 'static/css/libs.min.css'
			}
		},
		watch: {
			options: {
				nospawn: true
			},
			css: {
				files: ['dev/**/*.css'],
				tasks: ['concat:css_libs', 'cssmin:css_libs']
			},
			SkinsLess: {
				files: ['skins/**/less/**/*.less'],
				tasks: ['less']
			}
		}
	});

	// dependencies
	for (var key in grunt.file.readJSON('package.json').devDependencies) {
		if (key.indexOf('grunt-') === 0) {
			grunt.loadNpmTasks(key);
		}
	}

	grunt.registerTask('default', ['concat', 'less', 'cssmin']);
	grunt.registerTask('w', ['default', 'watch']);
	grunt.registerTask('watch-css-only', ['w']);
};
