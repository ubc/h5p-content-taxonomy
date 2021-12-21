import React from 'react';
import Select from './select2';

export default props => {
    const displine_list = ubc_h5p_admin.disciplines_list;
    const { disciplineSelected, setDisciplineSelected, isMulti } = props;
    return (
        <Select
            selected={ disciplineSelected }
            options={ displine_list }
            placeholder="Select Discipline..."
            setSelected={ setDisciplineSelected }
            name="ubc-h5p-content-discipline"
            isMulti={ isMulti }
        />
    );
}