import React from 'react';

export default props => {
    return (
        <div className="postbox taxonomy">
            <div role="button" className="h5p-toggle" tabIndex="0" aria-expanded="true" aria-label="Toggle panel"></div>
            <h2>{ props.label }</h2>
            <div className="h5p-panel custom-taxonomy">
                { props.children }
            </div>
        </div>
    );
};