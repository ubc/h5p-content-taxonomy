import React, {useState, useEffect, Fragment} from 'react';

export default ( props ) => {

    const [data, setData] = useState([]);
    const [offset, setOffset] = useState(0);
    const [sort, SetSort] = useState(4);
    const [countTotal, setCountTotal] = useState(0);
    const limit = 20;
    const sortDir = 0;

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
    const [currentTab, setCurrentTab] = useState(0);

    useEffect(() => {
        async function fetch() {
            let data = await fetchFromAPI();
            setData( data.data.data );
            setCountTotal( data.data.num );
        }
        
        fetch();
    }, [ currentTab, offset, sort ]);

    const fetchFromAPI = async () => {
        let formData = new FormData();

        formData.append( 'action', 'ubc_h5p_list_contents' );
        formData.append( 'offset', offset );
        formData.append( 'limit', limit );
        formData.append( 'sortBy', sort );
        formData.append( 'sortDir', sortDir );
        formData.append( 'context', tabOptions[currentTab].slug );
        formData.append( 'nonce', h5p_listing_view_obj.security_nonce );

        let response = await fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        response = await response.json();

        return response;
    }

    const hasPreviousPage = () => {
        return offset - limit >= 0;
    }

    const goToPreviousPage = () => {
        if( hasPreviousPage() ) {
            setOffset( offset - limit );
        }
    }

    const hasNextPage = () => {
        return offset + limit < countTotal;
    }

    const goToNextPage = () => {
        if( hasNextPage() ) {
            setOffset(offset + limit);
        }
    }

    return (
        <Fragment>
            <div className="h5p-button-groups">
                { tabOptions.map( (tab, index) => {
                    return ! h5p_listing_view_obj.can_user_editor_others && tab.slug === 'faculty' ? null : <button
                            className={`${currentTab === index ? 'active' : ''}`}
                            key={ index }
                            onClick={ e => {
                                currentTab !== index ? setCurrentTab( index ) : null;
                                setOffset(0);
                                SetSort(4);
                            }}
                        >
                            { tab.label}
                        </button>
                }) }
            </div>
            { data ? <table className="wp-list-table widefat fixed" style={{ marginTop: '20px' }}>
                <thead>
                    <tr>
                        <th
                            role="button"
                            tabIndex="0"
                        >Title</th>
                        <th role="button" tabIndex="0">Content Type</th>
                        <th role="button" tabIndex="0">Author</th>
                        <th role="button" tabIndex="0">Tags</th>
                        <th role="button" tabIndex="0">Last Modified</th>
                        <th role="button" tabIndex="0">ID</th>
                        <th role="button" tabIndex="0"></th>
                        <th role="button" tabIndex="0"></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <td colSpan="8">
                            <div className="h5p-pagination">
                                <button
                                    className="button"
                                    title="Previous page"
                                    disabled={ ! hasPreviousPage() }
                                    onClick={ () => {
                                        goToPreviousPage();
                                    } }
                                >&lt;</button>
                                <span>{`Page ${(offset + limit) / limit} of ${Math.ceil(countTotal / limit)}`}</span>
                                <button
                                    className="button"
                                    title="Next page"
                                    disabled={ ! hasNextPage() }
                                    onClick={ () => {
                                        goToNextPage();
                                    } }
                                >&gt;</button>
                            </div>
                        </td>
                    </tr>
                </tfoot>
                <tbody>
                { data.map( (entry, index) => {
                    const formattedTags = entry.tags ? entry.tags.split(';').map(tag => {
                        return tag.split(',')[1];
                    }).join(',') : '';

                    return (
                        <tr key={ index }>
                            <td>{ entry.title }</td>
                            <td>{ entry.content_type }</td>
                            <td>{ entry.user_name }</td>
                            <td>{ formattedTags }</td>
                            <td>{ entry.updated_at }</td>
                            <td>{ entry.id }</td>
                            <td><a href={`${h5p_listing_view_obj.admin_url}admin.php?page=h5p&task=results&id=${entry.id}`}>Results</a></td>
                            <td><a href={`${h5p_listing_view_obj.admin_url}admin.php?page=h5p_new&id=${entry.id}`}>Edit</a></td>
                        </tr>
                    );
                }) }
                </tbody>
            </table> : null }
        </Fragment>
    );
}