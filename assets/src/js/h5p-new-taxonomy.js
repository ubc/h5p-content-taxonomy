import React, {useState, useEffect} from 'react';
import 'url-search-params-polyfill';
import MetaBoxWrap from './h5p-new-taxonomy-metabox-wrap';
import { format2levelTermsOptions, retriveObjectsFrom2levelTermsOptions } from './helper.js';
import Select from './select2';

export default ( props ) => {
    
    const [facultySelected, setFacultySelected] = useState([]);
    const [disciplineSelected, setDisciplineSelected] = useState([]);

    useEffect(() => {
        /**
         * Few conditions to check with priority
         * 
         * 1. If the content already has faculties attached, then we use the faculties saved from database.
         * 2. If the content does not have faculty attached, then we set the user's faculties as default.
         */
        const selectedFacultyFromDB = ubc_h5p_admin.content_faculty ? retriveObjectsFrom2levelTermsOptions(ubc_h5p_admin.content_faculty, ubc_h5p_admin.faculties_list) : [];
        const userFaculty = ubc_h5p_admin.user_faculty ? retriveObjectsFrom2levelTermsOptions(ubc_h5p_admin.user_faculty, ubc_h5p_admin.faculties_list) : [];
        setFacultySelected( selectedFacultyFromDB && selectedFacultyFromDB.length !== 0 ? selectedFacultyFromDB : ( userFaculty ? userFaculty : [] ) );

        /**
         * If the content already has disciplines attached, then we use the disciplines save from database.
         */
        const selectedDisciplineFromDB = ubc_h5p_admin.content_discipline ? retriveObjectsFrom2levelTermsOptions(ubc_h5p_admin.content_discipline, ubc_h5p_admin.disciplines_list) : [];
        setDisciplineSelected( selectedDisciplineFromDB ? selectedDisciplineFromDB : [] );
    }, []);

    return (
        <div>
            <MetaBoxWrap label="Faculty">
                 <Select
                    selected={ facultySelected }
                    isMulti={ true }
                    options={ format2levelTermsOptions(ubc_h5p_admin.faculties_list) }
                    placeholder="Select Faculty..."
                    setSelected={ setFacultySelected }
                    name="ubc-h5p-content-faculty"
                />
            </MetaBoxWrap>

            <MetaBoxWrap label="Discipline">
                <Select
                    selected={ disciplineSelected }
                    isMulti={ true }
                    options={ format2levelTermsOptions(ubc_h5p_admin.disciplines_list) }
                    placeholder="Select Discipline..."
                    setSelected={ setDisciplineSelected }
                    name="ubc-h5p-content-discipline"
                />
            </MetaBoxWrap>
            <input
                type="hidden"
                value={ JSON.stringify({
                    faculty: facultySelected.map(faculty => {
                        return faculty.value;
                    }),
                    discipline: disciplineSelected.map(discipline => {
                        return discipline.value;
                    })
                }) }
                name="ubc-h5p-content-taxonomy"
            />
        </div>
    );
}