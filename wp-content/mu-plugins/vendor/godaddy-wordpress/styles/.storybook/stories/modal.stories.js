/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import { wordpress } from '@wordpress/icons';
import {
	Button,
	Icon,
	Modal,
} from '@wordpress/components';

export default {
	title: 'Components/Modal',
	component: Modal,
	argTypes: {
		title: { control: 'text', defaultValue: 'Modal Title' },
		showIcon: { control: 'boolean', defaultValue: false },
	},
};

export const _default = ( props ) => {
	return <ModalExample className="godaddy-styles" { ...props } />;
};

const ModalExample = ( props ) => {
	const [ isOpen, setOpen ] = useState( true );
	const openModal = () => setOpen( true );
	const closeModal = () => setOpen( false );

	return (
		<>
			<Button className="godaddy-styles" variant="secondary" onClick={ openModal }>
				Open Modal
			</Button>
			{ isOpen && (
				<Modal onRequestClose={ closeModal }
					icon={ props.showIcon ? <Icon icon={ wordpress } /> : null }
					{ ...props }
				>
					<Button className="godaddy-styles" variant="primary" onClick={ closeModal }>
						Primary
					</Button>
					<Button className="godaddy-styles" variant="secondary" onClick={ closeModal }>
						Secondary
					</Button>
				</Modal>
			) }
		</>
	);
};
