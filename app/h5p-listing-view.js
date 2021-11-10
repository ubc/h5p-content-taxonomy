import React from 'react';
import ReactDOM from 'react-dom';
import DataListing from '../assets/src/js/h5p-data-listing';

import '../assets/src/css/h5p-listview.scss';

ReactDOM.render(
	<DataListing />,
	// eslint-disable-next-line no-undef
	document.getElementById( 'h5p-contents' )
);
