// Code venant de gist :  @jbmoelker : jbmoelker/bootstrap.js
// uri : https://gist.github.com/jbmoelker/b8c3c4359db7ca94439f
//
// Create shim for requested bootstrap component and require
// and return jQuery so you do not have to inject it separately
// every time you use a bootstrap component.
define({
    load: function (name, req, onload, config) {
    // Set this path to wherever the bootstrap components live.
    // Contents should match https://github.com/twbs/bootstrap/tree/master/js
		var component = '../node_modules/bootstrap/js/dist/'+name;

		var shim = {};
		shim[component] = {
			deps: ['jquery'],
			exports: '$.fn.'+name
		}
        require.config({ shim: shim });

        req(['jquery', component], function ($, value) {
            // return jQuery, just like jQuery's own $.fn methods do
            onload($);
        });
    }
});