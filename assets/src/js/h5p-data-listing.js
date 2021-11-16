import React, {useState, useEffect, Fragment} from 'react';

export default ( props ) => {

    const [data, setData] = useState([]);
    const [offset, setOffset] = useState(0);
    const [sort, setSort] = useState(0);
    const [revert, setRevert] = useState(false);
    const [countTotal, setCountTotal] = useState(0);
    const [search, setSearch] = useState('');
    const limit = 20;

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
        doFetch();
    }, [ currentTab, offset, sort, revert ]);

    const doFetch = () => {
        async function fetch() {
            let data = await fetchFromAPI();
            setData( data.data.data );
            setCountTotal( data.data.num );
        }
        
        fetch();
    }

    const updateSort = newSort => {
        if( -1 === newSort ) {
            setSort(0);
            setRevert(false);
        }else{
            if( sort === newSort ) {
                setRevert(!revert);
            } else {
                setSort(newSort);
                setRevert(false);
            }
        }
    }

    const fetchFromAPI = async () => {
        let formData = new FormData();

        formData.append( 'action', 'ubc_h5p_list_contents' );
        formData.append( 'offset', offset );
        formData.append( 'limit', limit );
        formData.append( 'sortBy', sort );
        formData.append( 'revert', revert );
        formData.append( 'search', search );
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
                                updateSort( -1 );
                            }}
                        >
                            { tab.label}
                        </button>
                }) }
            </div>
            <div>
                <input
                    type="text"
                    id="search"
                    placeholder="Search..."
                    onKeyUp={ e => {
                        if (e.key === 'Enter' || e.keyCode === 13) {
                            doFetch();
                        }
                    }}
                    onChange={ e => {
                        setSearch(e.target.value);
                    }}
                    value={search}
                />
            </div>
            { data ? <table className="wp-list-table widefat fixed" style={{ marginTop: '20px' }}>
                <thead>
                    <tr>
                        <th
                            role="button"
                            tabIndex="0"
                            onClick={() => {
                                updateSort(1);
                            }}
                        >Title</th>
                        <th
                            role="button"
                            tabIndex="0"
                            onClick={() => {
                                updateSort(2);
                            }}
                        >Content Type</th>
                        <th
                            role="button"
                            tabIndex="0"
                            onClick={() => {
                                updateSort(3);
                            }}
                        >Author</th>
                        <th tabIndex="0">Tags</th>
                        <th
                            role="button"
                            tabIndex="0"
                            onClick={() => {
                                updateSort(0);
                            }}
                        >Last Modified</th>
                        <th
                            role="button"
                            tabIndex="0"
                            onClick={() => {
                                updateSort(4);
                            }}
                        >ID</th>
                        <th tabIndex="0"></th>
                        <th tabIndex="0"></th>
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
                    const formattedTags = entry.tags ? entry.tags.split(';').join(',') : '';

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