import React from 'react';
import ReactDOM from 'react-dom';
import App from '../assets/src/js/h5p-new-taxonomy';
import '../assets/src/css/h5p-new.scss';

var div = document.getElementById('post-body-content');
div.insertAdjacentHTML('beforeend', '<div id=\"h5p-taxonomy\"></div>');
div.insertAdjacentHTML('beforeend', "");

ReactDOM.render(
	<App tags={[]} />,
	// eslint-disable-next-line no-undef
	document.getElementById( 'h5p-taxonomy' )
);
