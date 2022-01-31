import React from 'react';
import ReactSelect, { createFilter } from 'react-select';
import CustomOption from './select2-option';

export default props => {
    const { selected, options, setSelected, isMulti=true } = props;

    // selected is an array of options values. Need to convert to proper data structure
    let newSelected = isMulti ? [] : '';
    // Reformat options to required data structure.
    let newOptions = Object.keys(options).map( campusIndex => {
        return {
            label: options[campusIndex].name,
            options: options[campusIndex].children ? Object.keys(options[campusIndex].children).map( facultyIndex => {
                const faculty = {
                    label: options[campusIndex].children[facultyIndex].name,
                    value: options[campusIndex].children[facultyIndex].term_id
                };

                if( isMulti && selected.includes( options[campusIndex].children[facultyIndex].term_id ) ) {
                    newSelected.push( faculty );
                }

                if( !isMulti && selected === options[campusIndex].children[facultyIndex].term_id ) {
                    newSelected = faculty;
                }

                return faculty;
            }) : []
        }
    });

    if( ! isMulti ) {
        newOptions.unshift({
            label: 'All',
            value: ''
        });
    }

    return (
        <ReactSelect
            value={newSelected}
            isMulti={isMulti}
            options={newOptions}
            placeholder={props.placeholder}
            classNamePrefix="custom-select"
            components={{ Option: CustomOption }}
            filterOption={createFilter({ ignoreAccents: false })}
            onChange={optionSelected => {
                // Convert it back to array of ids.
                setSelected( isMulti ? optionSelected.map( option => {
                    return option.value;
                }) : optionSelected.value);
            }}
        />
    );
};