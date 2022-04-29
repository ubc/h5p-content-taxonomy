import React from 'react';
import ReactSelect, { createFilter } from 'react-select';
import CustomOption from './select2-option';

export default props => {
    const { selected, options, onChange, placeholder, name, isMulti=true } = props;

    return (
        <ReactSelect
            value={selected}
            isMulti={isMulti}
            options={isMulti ? options : [
                {
                    label: 'All',
                    value: null
                },
                ...options,

            ]}
            placeholder={placeholder}
            classNamePrefix={name}
            components={{ Option: CustomOption }}
            filterOption={createFilter({ ignoreAccents: false })}
            onChange={optionSelected => {
                onChange( optionSelected );
            }}
        />
    );
};