import React, {Fragment} from 'react';
import Select from './select2';

export default props => {
    const faculty_list = ubc_h5p_admin.faculties_list;
    const { facultySelected, setFacultySelected } = props;
    return (
        <Select
            selected={ facultySelected }
            options={ faculty_list }
            setSelected={ setFacultySelected }
            name="ubc-h5p-content-faculty"
        />
    );
}