import React, {useState, useEffect, Fragment} from 'react';

export default ( props ) => {
    const tabOptions = [
        {
            label: 'My H5P contents',
            slug: 'self'
        },
        {
            label: 'My faculty H5P contents',
            slug: 'faculty'
        }
    ];
    const [tab, setTab] = useState(tabOptions[0]);

    return (
        <div className="h5p-button-groups">
            { tabOptions.map( (tab, index) => {
                return <button key={ index }>{ tab.label}</button>
            }) }
        </div>
    );
}