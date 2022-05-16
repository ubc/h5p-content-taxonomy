import React, { useState, useEffect, Fragment, useRef } from 'react';
import Select from './select2';
import { format2levelTermsOptions } from './helper.js';

window.h5pTaxonomy = {};
window.h5pTaxonomy.listView = {};

export default () => {

    const [data, setData] = useState([]);
    const [offset, setOffset] = useState(0);
    const [sort, setSort] = useState(0);
    const [revert, setRevert] = useState(false);
    const [countTotal, setCountTotal] = useState(0);
    const [search, setSearch] = useState('');
    const [facultySelected, setFacultySelected] = useState('');
    const [disciplineSelected, setDisciplineSelected] = useState('');
    const [tagSelected, setTagSelected] = useState(null);
    const limit = 20;

    const previousOffset = useRef( 0 );

    const tabOptions = wp.hooks.applyFilters('h5p-listing-view-additional-tab', ubc_h5p_admin.is_user_admin ? [
        {
            label: 'My H5P Content',
            slug: 'self'
        },
        {
            label: 'Community H5P Contents',
            slug: 'admin'
        }
    ] : [
        {
            label: 'My H5P Content',
            slug: 'self'
        },
        {
            label: 'My Faculty H5P Content',
            slug: 'faculty'
        }
    ]);
    const [currentTab, setCurrentTab] = useState(0);

    // Turn array of strings to array of integers for further comparison.
    const userFacultyIds = ubc_h5p_admin.user_faculty.map(faculty => {
        return parseInt(faculty);
    })
    // List of faculty objects that belongs to current user.
    const userFaculty = ubc_h5p_admin.faculties_list.map(campus => {
        return { 
                ...campus, 
                children: campus.children.filter(faculty => {
                    return userFacultyIds.includes(faculty.term_id);
                })
            }
    });
    // Entire faculty list.
    const allFaculty = ubc_h5p_admin.faculties_list;

    // Save the previous Offset value.
    useEffect(() => {
        doFetch();
    }, [ offset ]);

    // Refetch the result from database if any of the filter changes.
    // This is a critical fix. The offset need to be reset after any of the filter changed.
    useEffect(() => {
        if( offset === 0 ) {
            doFetch();
        } else {
            setOffset( 0 );
        }
    }, [ search, currentTab, sort, revert, facultySelected, disciplineSelected, tagSelected ]);

    // Fetch data from the database.
    const doFetch = () => {
        async function fetch() {
            let data = await fetchFromAPI();
            setData( data.data.data );
            setCountTotal( data.data.num );
        }
        
        fetch();
    }
    window.h5pTaxonomy.listView.doFetch = doFetch;

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
        let terms = [];

        if( facultySelected && facultySelected.value ) {
            terms.push(facultySelected.value);
        }
        if( disciplineSelected && disciplineSelected.value ) {
            terms.push(disciplineSelected.value);
        }

        formData.append( 'action', 'ubc_h5p_list_contents' );
        formData.append( 'offset', offset );
        formData.append( 'limit', limit );
        formData.append( 'sortBy', sort );
        formData.append( 'revert', revert );
        formData.append( 'search', search );
        formData.append( 'tags', JSON.stringify(tagSelected) )
        formData.append( 'context', tabOptions[currentTab].slug );
        formData.append( 'nonce', ubc_h5p_admin.security_nonce );
        formData.append( 'terms', JSON.stringify(terms));

        formData = wp.hooks.applyFilters('h5p-listing-view-additional-form-data', formData, tabOptions[currentTab]);

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

    const moreFilters = () => {
        return wp.hooks.applyFilters('h5p-listing-view-additional-filters', '', tabOptions[currentTab]);
    };

    return data ? (
        <Fragment>
            { ! ubc_h5p_admin.can_user_editor_others ? null : <div className="h5p-button-groups">
                { tabOptions.map( (tab, index) => {
                    return  <button
                            className={`${currentTab === index ? 'active' : ''}`}
                            key={ index }
                            onClick={ e => {
                                currentTab !== index ? setCurrentTab( index ) : null;
                            }}
                        >
                            { tab.label}
                        </button>
                }) }
            </div> }
            <div id="h5p-filters">
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
                <Select
                    selected={ facultySelected }
                    isMulti={ false }
                    options={ format2levelTermsOptions(ubc_h5p_admin.is_user_admin ? allFaculty : userFaculty) }
                    placeholder="Select Faculty..."
                    onChange={ optionSelected => {
                        setFacultySelected(optionSelected);
                    } }
                    name="ubc-h5p-content-faculty"
                />
                <Select
                    selected={ disciplineSelected }
                    isMulti={ false }
                    options={ format2levelTermsOptions(ubc_h5p_admin.disciplines_list) }
                    placeholder="Select Discipline..."
                    onChange={ optionSelected => {
                        setDisciplineSelected(optionSelected);
                    } }
                    name="ubc-h5p-content-discipline"
                />
                <Select
                    selected={tagSelected}
                    placeholder='Select Tags...'
                    isMulti
                    options={ ubc_h5p_admin.tag_list.map( tag => {
                        return {
                            value: tag.id,
                            label: tag.name
                        };
                    }) }
                    onChange={optionSelected => {
                        setTagSelected( optionSelected );
                    }}
                />

                <div id="h5p-more-filters">
                    { moreFilters() }    
                </div>
            </div>

            <table className="wp-list-table widefat fixed" style={{ marginTop: '20px' }}>
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
                        <th tabIndex="0" className="faculty-tab">Faculties</th>
                        <th tabIndex="0" className="tag-tab">Tags</th>
                        <th
                            role="button"
                            tabIndex="0"
                            onClick={() => {
                                updateSort(0);
                            }}
                        >Last Modified</th>
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
                    const faculties = entry.faculty.map( fac => {
                        return fac.name;
                    });

                    return (
                        <tr key={ index }>
                            <td><a href={`${ubc_h5p_admin.admin_url}admin.php?page=h5p_new&id=${entry.id}`}>{ entry.title }</a></td>
                            <td>{ entry.content_type }</td>
                            <td>{ entry.user_name }</td>
                            <td>{ faculties.join(',') }</td>
                            <td>{ formattedTags }</td>
                            <td>{ entry.updated_at }</td>
                            <td><a href={`${ubc_h5p_admin.admin_url}admin.php?page=h5p&task=results&id=${entry.id}`}>Results</a></td>
                        </tr>
                    );
                }) }
                </tbody>
            </table>
        </Fragment>
    ) : null;
}