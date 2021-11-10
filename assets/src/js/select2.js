import React, { CSSProperties } from 'react';

import Select from 'react-select';


export default props => {
    const { selected, options, setSelected } = props;

    // selected is an array of options values. Need to convert to proper data structure
    let newSelected = [];

    // Reformat options to required data structure.
    let newOptions = Object.keys(options).map( campusIndex => {
        return {
            label: options[campusIndex].name,
            options: options[campusIndex].children ? Object.keys(options[campusIndex].children).map( facultyIndex => {
                const faculty = {
                    label: options[campusIndex].children[facultyIndex].name,
                    value: options[campusIndex].children[facultyIndex].term_id
                };

                if( selected.includes( options[campusIndex].children[facultyIndex].term_id ) ) {
                    newSelected.push( faculty );
                }
                return faculty;
            }) : []
        }
    });

    return (
        <Select
            value={newSelected}
            isMulti
            options={newOptions}
            onChange={optionSelected => {
                // Convert it back to array of ids.
                setSelected( optionSelected.map( option => {
                    return option.value;
                }));
            }}
        />
    );
};