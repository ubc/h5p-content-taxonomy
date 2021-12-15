import React, { useEffect, useState } from 'react';
import { Tab, Tabs, TabList, TabPanel } from 'react-tabs';
import 'react-tabs/style/react-tabs.css';

export default () => {
    const queryParams = new URLSearchParams(window.location.search);
    const index = queryParams.get('tab');

    const [tabIndex, setTabIndex] = useState( index ? parseInt(index) : 0);
    const [submited, setSubmited] = useState( queryParams.get('submited') === 'true' );
    const [success, setSuccess] = useState( queryParams.get('success') === 'true' );

    useEffect( () => {
        const queryParams = new URLSearchParams(window.location.search);
        queryParams.set('tab', tabIndex);

        var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?' + queryParams.toString();
        window.history.pushState({path:newurl},'',newurl);
    }, [tabIndex]);

    const exportTerms = () => {
        return (
            <div className="postbox">
                <form method="post">
                    <table className="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><label htmlFor="default_category">Taxonomies</label></th>
                                <td>
                                    <label><input type="radio" name="taxonomies" value="all" defaultChecked/> All</label>
                                    <label><input type="radio" name="taxonomies" value="faculty" /> Faculty</label>
                                    <label><input type="radio" name="taxonomies" value="discipline" /> Discipline</label>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <input
                                        name="action"
                                        type="text"
                                        hidden
                                        value="export_terms"
                                    />
                                    <input
                                        type="submit"
                                        className="button button-primary"
                                        value="Export"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>
            </div>
        );
    }

    const importTerms = () => {
        return (
            <div className="postbox">
                <form method="post" encType="multipart/form-data" onSubmit={ e => { fileValidation( e ) } }>
                    <table className="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <td>
                                    <input
                                        name="action"
                                        type="text"
                                        hidden
                                        value="import_terms"
                                    />
                                    <input
                                        type="file"
                                        id="import_terms_file"
                                        name="import_terms_file"
                                    />
                                    <input
                                        type="submit"
                                        className="button button-primary"
                                        value="Import"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    { submited && !success ? <p className="error">Upload failed. Please double check the upload file.</p> : null }
                    { submited && success ? <p className="success">Terms have been added successfully.</p> : null }
                </form>
            </div>
        );
    }

    const fileValidation = e => {
        const pass = document.getElementById('import_terms_file').files && document.getElementById('import_terms_file').files.length !== 0;

        if( ! pass ) {
            setSubmited( true );
            setSuccess( false );
            e.preventDefault();
        }
    }

    return (
        <div className="wrap">
            <h2>Import/Export</h2>
            <Tabs selectedIndex={tabIndex} onSelect={index => setTabIndex(index)}>
                <TabList>
                    <Tab>Export Terms</Tab>
                    <Tab>Import Terms</Tab>
                </TabList>

                <TabPanel>
                    { exportTerms() }
                </TabPanel>
                <TabPanel>
                    { importTerms() }
                </TabPanel>
            </Tabs>
        </div>
    );
}