import React from 'react';
import Select from './select2';

export default props => {
    const faculty_list = ubc_h5p_admin.faculties_list;
    const { facultySelected, setFacultySelected, isMulti } = props;
    return (
        <Select
            selected={ facultySelected }
            options={ faculty_list }
            placeholder="Select Faculty..."
            setSelected={ setFacultySelected }
            name="ubc-h5p-content-faculty"
            isMulti={ isMulti }
        />
    );
}