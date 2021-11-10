import React, {useState, useEffect, useCallback} from 'react';
import 'url-search-params-polyfill';
import Faculty from './h5p-new-taxonomy-faculty';
import Discipline from './h5p-new-taxonomy-discipline';
import MetaBoxWrap from './h5p-new-taxonomy-metabox-wrap';

export default ( props ) => {
    
    const [facultySelected, setFacultySelected] = useState([]);
    const [disciplineSelected, setDisciplineSelected] = useState([]);

    useEffect(() => {
        const selectedFacultyFromDB = ubc_h5p_admin.content_faculty ? ubc_h5p_admin.content_faculty : [];
        setFacultySelected( selectedFacultyFromDB && selectedFacultyFromDB.length !== 0 ? selectedFacultyFromDB : ( ubc_h5p_admin.user_faculty ? ubc_h5p_admin.user_faculty.map( faculty_id => {
            return parseInt( faculty_id );
        }) : [] ) );

        const selectedDisciplineFromDB = ubc_h5p_admin.content_discipline ? ubc_h5p_admin.content_discipline : [];
        setDisciplineSelected( selectedDisciplineFromDB ? selectedDisciplineFromDB : [] );
    }, []);

    return (
        <div>
            <MetaBoxWrap label="Faculty">
                <Faculty
                    facultySelected={ facultySelected }
                    setFacultySelected = { setFacultySelected }
                />
            </MetaBoxWrap>

            <MetaBoxWrap label="Discipline">
                <Discipline
                    disciplineSelected={ disciplineSelected }
                    setDisciplineSelected = { setDisciplineSelected }
                />
            </MetaBoxWrap>
            <input
                type="hidden"
                value={ JSON.stringify({
                    faculty: facultySelected,
                    discipline: disciplineSelected
                }) }
                name="ubc-h5p-content-taxonomy"
            />
        </div>
    );
}