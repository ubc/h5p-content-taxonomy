import React from 'react';
import Select from './select2';

export default props => {
    const displine_list = ubc_h5p_admin.disciplines_list;
    const { disciplineSelected, setDisciplineSelected } = props;
    return (
        <Select
            selected={ disciplineSelected }
            options={ displine_list }
            setSelected={ setDisciplineSelected }
            name="ubc-h5p-content-discipline"
        />
    );
}