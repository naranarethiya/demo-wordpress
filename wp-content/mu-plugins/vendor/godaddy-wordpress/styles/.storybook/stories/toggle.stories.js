/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { ToggleControl } from '@wordpress/components';

export default {
	title: 'Components/ToggleControl',
	component: ToggleControl,
	argTypes: {
		label: { control: 'text', defaultValue: 'Toggle Control' },
		help: { control: 'text', defaultValue: 'Help text' },
	},
};

export const _default = ( props ) => {
	const [ isChecked, setChecked ] = useState( false );
	return <ToggleControl className="godaddy-styles"
		onChange={ () => setChecked( ( state ) => ! state ) }
		checked={ isChecked }
		{ ...props }
	/>;
};
