window.addEventListener('load', () => {
    matchHeight('custom-box-title');
	matchHeight('custom-box-content');
	matchHeight('custom-box-footer');
});

var resizeAction;
window.addEventListener('resize', () => {
	clearTimeout(resizeAction);
	resizeAction = setTimeout(()=> {
		matchHeight('custom-box-title');
		matchHeight('custom-box-content');
		matchHeight('custom-box-footer');
	}, 500);
	
});


/**
 * Matches height of all elements with className
 */
function matchHeight(className) {
	var elementsToMatch = document.getElementsByClassName(className);
	var maxHeight = 0;
	for (let elm of elementsToMatch) {
		elm.style.height = 'auto';
		if (elm.offsetHeight > maxHeight) {
			maxHeight = elm.offsetHeight;
		}
	}
	for (let elm of elementsToMatch) {
		elm.style.height = maxHeight + 'px';
	}	
}