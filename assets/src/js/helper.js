/**
 * The function converts nested array of taxonomy term objects such as faculty and discipline in to {label, value}
 * that can be directly used in the Select component.
 * It has to follow certain structure. Faculty and discipline as an example.
 * The first level of terms are always going to be the group. The seconds level terms are the actual options.
 * @param {array} options Nested array of taxonomy terms.
 * @returns 
 */
export const format2levelTermsOptions = ( options ) => {
    return options.map( firstLevelOption => {
        return {
            label: firstLevelOption.name,
            options: firstLevelOption.children ? firstLevelOption.children.map( secondLevelOption => {
                return {
                    label: secondLevelOption.name,
                    value: secondLevelOption.term_id
                };
            }) : []
        };
    });
};

/**
 * Retrive array of term objects based on array of term ids.
 */
export const retriveObjectsFrom2levelTermsOptions = ( objectIDArray, options ) => {
    const newOptions = [];

    options.forEach(firstLevelOption => {
        firstLevelOption.children.forEach(secondLevelOption => {
            if( objectIDArray.includes(secondLevelOption.term_id) ) {
                newOptions.push( {
                    label: secondLevelOption.name,
                    value: secondLevelOption.term_id
                } );
            }
        });
    });

    return newOptions;
}