requirejs.config({
    baseUrl: "js/",
    'paths': {
	// from v1.0.5 requirejs : //requirejs.org/docs/release/1.0.5/minified/order
	'order': 'lib/order',
	'jquery' :'../node_modules/jquery/dist/jquery.min',
        'bootstrap' : '../node_modules/bootstrap/dist/js/bootstrap.bundle.min',
        'nouislider' : '../node_modules/nouislider/dist/nouislider.min',
        'moment' : '../node_modules/moment/min/moment-with-locales.min',
    },
    'shims' : {
        'jquery': {
	    exports: '$'
        },
        'bootstrap': {
            deps: ['jquery'],
            exports: '$'
        },
        'autocompleteUser': {
            exports: '$'
        },
        'nouislider': {
            exports: 'noUiSlider'
        },
        'slider': {
            deps: ['nouislider']
        }
    },
// out: tentative d'optimisation de requirejs
    out: 'rjsopt.js'
}
);

requirejs(['order!form', 'order!evento']);

