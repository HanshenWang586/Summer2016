(function() {

$(init);

function processCategories(context) {
	context = $(context || this);
	context.find('li.groupCategory:not(.jq_hover)').addClass('jq_hover').hoverClass('hover');
}

function init() {
	processCategories('ul.groupCategories');
	
	var ajaxNav = $('#catGroups a').ajaxNav({
		target: 'ul.groupCategories',
		//root: rootURL,
		//trackGA: true,
		createWorkspace: true,
		createViewport: true,
		fixViewport: true,
		hashStyle: 'text',
		//dynamicWidth: true,
		//root: rootLang,
		//hashPath: hashPath,
		//minWidth: 750,
		callback: processCategories,
		//beforeLoad: showLoader,
		//onLoad: hideLoader,
		//dynamicWidthCallback: homepageContentResize,
		//callback: processContent,
		ajaxParams: {view: 'default', get: 'categoryGroup', output: 'json'},
		classActive: 'active',
		keyNav: true,
		resizeViewport: true
	});
}

})();