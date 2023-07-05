/**
 * External dependencies
 */
import { registerBlockCollection } from '@wordpress/blocks';

/**
 * Registers a block collection from the provided data.
 *
 * @param {Object} blockCollection
 */
const createBlockCollection = ( blockCollection ) => {
	const {
		namespace,
		title,
		icon,
	} = blockCollection;

	registerBlockCollection( namespace, {
		title,
		icon,
	} );
};

export default createBlockCollection;
