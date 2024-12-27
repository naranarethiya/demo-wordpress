/**
 * WordPress dependencies
 */
import { Button } from '@wordpress/components';

export default {
	title: 'Components/Button',
	component: Button,
	argTypes: {
		text: { control: 'text', defaultValue: 'Button Text' },
		variant: {
			control: 'select',
			options: [ 'primary', 'secondary', 'tertiary', 'link' ],
			defaultValue: 'primary',
		},
	},
};

export const _default = ( props ) => {
	return <Button className="godaddy-styles" { ...props } />;
};
