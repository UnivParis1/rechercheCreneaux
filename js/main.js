requirejs.config({
    baseUrl: "js/",
    'paths': {
        'bootstrap': 'lib/jbmoelker/requirejs-bootstrap-plugin',
        'jquery' :'../node_modules/jquery/dist/jquery.min',
        'nouislider' : '../node_modules/nouislider/dist/nouislider.min',
        'moment' : '../node_modules/moment/min/moment-with-locales.min',
        '@popperjs/core': '../node_modules/@popperjs/core/dist/umd/popper.min'
    },
    'shims' : {
        'jquery': {
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
    }
});

requirejs(['form', 'evento']);

