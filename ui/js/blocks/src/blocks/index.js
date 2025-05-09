/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import parse from 'html-react-parser';

/**
 * Internal dependencies
 */
import createBlockEditComponent from './createBlockEditComponent';
import createAttributesFromFields from './createAttributesFromFields';

/**
 * Registers a block from the provided data.
 *
 * @param {Object} block
 */
const createBlock = ( block ) => {
	const {
		blockName,
		fields,
	} = block;

	let icon = block.icon;

	if ( 'pods' === icon ) {
		// Handle the default Pods SVG icon.
		icon = (
			<svg width="366" height="364" viewBox="0 0 366 364" fill="none" xmlns="http://www.w3.org/2000/svg"><mask id="mask0" mask-type="alpha" maskUnits="userSpaceOnUse" x="20" y="20" width="323" height="323"><path fillRule="evenodd" clipRule="evenodd" d="M20.9831 181.536C20.9831 270.512 93.6969 342.643 183.391 342.643V342.643C249.369 342.643 306.158 303.616 331.578 247.565V247.565C324.498 226.102 318.371 188.341 342.809 150.596V150.596C341.764 145.264 340.453 140.028 338.892 134.9V134.9C263.955 208.73 203.453 215.645 157.9 214.441V214.441C157.479 214.428 155.947 214.182 155.54 213.271V213.271C155.54 213.271 154.876 210.318 156.817 210.318V210.318C244.089 210.318 293.793 159.374 334.401 122.125V122.125C332.186 116.587 329.669 111.202 326.872 105.984V105.984C283.096 94.0368 266.274 58.4662 260.302 39.603V39.603C237.409 27.369 211.218 20.4269 183.391 20.4269V20.4269C93.6969 20.4269 20.9831 92.5574 20.9831 181.536V181.536ZM227.603 68.9767C227.603 68.9767 289.605 62.283 307.865 138.292V138.292C240.112 133.656 227.603 68.9767 227.603 68.9767V68.9767ZM202.795 100.959C202.795 100.959 257.765 99.1382 270.278 167.335V167.335C210.771 158.793 202.795 100.959 202.795 100.959V100.959ZM172.601 128.062C172.601 128.062 222.07 124.859 234.729 185.925V185.925C180.956 179.926 172.601 128.062 172.601 128.062V128.062ZM307.865 138.292C307.936 138.296 308 138.3 308.07 138.306V138.306L307.921 138.528C307.903 138.449 307.884 138.37 307.865 138.292V138.292ZM146.277 149.601C146.277 149.601 189.744 146.781 200.869 200.439V200.439C153.619 195.171 146.277 149.601 146.277 149.601V149.601ZM111.451 154.862C111.451 154.862 153.207 152.159 163.891 203.7V203.7C118.507 198.639 111.451 154.862 111.451 154.862V154.862ZM76.9799 157.234C76.9799 157.234 114.875 154.782 124.574 201.557V201.557C83.3817 196.962 76.9799 157.234 76.9799 157.234V157.234ZM39.5 164.081C39.5 164.081 71.2916 155.788 84.9886 193.798V193.798C83.4985 193.918 82.0535 193.976 80.6516 193.976V193.976C48.7552 193.975 39.5 164.081 39.5 164.081V164.081ZM310.084 167.245C310.06 167.175 310.035 167.102 310.013 167.033V167.033L310.233 167.093C310.184 167.143 310.134 167.194 310.084 167.245V167.245C333.75 238.013 291.599 276.531 291.599 276.531V276.531C291.599 276.531 261.982 216.144 310.084 167.245V167.245ZM270.278 167.335C270.337 167.343 270.396 167.351 270.455 167.36V167.36L270.317 167.544C270.305 167.473 270.293 167.406 270.278 167.335V167.335ZM234.729 185.925C234.782 185.931 234.838 185.937 234.89 185.943V185.943L234.769 186.111C234.756 186.046 234.744 185.988 234.729 185.925V185.925ZM275.24 192.061C275.232 191.992 275.224 191.919 275.218 191.849V191.849L275.405 191.966C275.35 191.999 275.296 192.03 275.24 192.061V192.061C282.645 263.228 236.486 286.583 236.486 286.583V286.583C236.486 286.583 221.57 223.215 275.24 192.061V192.061ZM85.0914 193.789L85.0296 193.912C85.0164 193.873 85.0009 193.834 84.9888 193.798V193.798C85.023 193.795 85.0572 193.792 85.0914 193.789V193.789ZM200.869 200.439C200.916 200.443 200.96 200.449 201.007 200.453V200.453L200.903 200.605C200.891 200.549 200.88 200.494 200.869 200.439V200.439ZM124.574 201.557C124.615 201.563 124.658 201.567 124.699 201.572V201.572L124.604 201.7C124.594 201.651 124.585 201.605 124.574 201.557V201.557ZM68.5101 213.185C68.5101 213.185 95.2467 187.93 129.068 216.089V216.089C118.738 224.846 108.962 227.859 100.399 227.859V227.859C81.5012 227.859 68.5101 213.185 68.5101 213.185V213.185ZM163.892 203.7C163.937 203.704 163.982 203.71 164.027 203.714V203.714L163.926 203.862C163.915 203.809 163.903 203.753 163.892 203.7V203.7ZM234.165 211.88C234.166 211.817 234.166 211.751 234.168 211.688V211.688L234.322 211.818C234.269 211.839 234.218 211.859 234.165 211.88V211.88C233.531 275.684 190.467 289.79 190.467 289.79V289.79C190.467 289.79 183.695 231.809 234.165 211.88V211.88ZM129.165 216.006V216.17C129.132 216.143 129.1 216.117 129.068 216.089V216.089C129.099 216.061 129.132 216.034 129.165 216.006V216.006ZM107.192 250.617C107.192 250.617 121.444 213.844 162.374 221.007V221.007C150.672 247.473 132.265 252.505 119.969 252.505V252.505C112.432 252.505 107.192 250.617 107.192 250.617V250.617ZM162.431 220.877L162.492 221.028C162.452 221.021 162.414 221.014 162.374 221.007V221.007C162.394 220.963 162.412 220.921 162.431 220.877V220.877ZM196.004 223.125C196.014 223.072 196.024 223.016 196.034 222.962V222.962L196.15 223.104C196.101 223.111 196.053 223.119 196.004 223.125V223.125C186.212 277.983 147.054 281.435 147.054 281.435V281.435C147.054 281.435 149.621 230.095 196.004 223.125V223.125Z" fill="white" /></mask><g mask="url(#mask0)"><path fillRule="evenodd" clipRule="evenodd" d="M9.95319 9.48413H353.838V353.586H9.95319V9.48413Z" fill="#95BF3B" /></g><path fillRule="evenodd" clipRule="evenodd" d="M182.999 11.2219C88.3278 11.2219 11.3101 87.6245 11.3101 181.535C11.3101 275.448 88.3278 351.85 182.999 351.85C277.67 351.85 354.69 275.448 354.69 181.535C354.69 87.6245 277.67 11.2219 182.999 11.2219M182.999 363.071C82.0925 363.071 0 281.633 0 181.535C0 81.4362 82.0925 0 182.999 0C283.905 0 366 81.4362 366 181.535C366 281.633 283.905 363.071 182.999 363.071" fill="#95BF3B" /></svg>
		);
	} else {
		// Support SVG icons by parsing it.
		icon = parse( icon );
	}

	const blockArgs = { ...block };

	delete blockArgs.blockName;
	delete blockArgs.fields;
	delete blockArgs.renderType;

	// Handle attributes shortcode function setup.
	const setupTransformAttributes = ( transform, blockAttributes ) => {
		const attributes = transform.attributes ?? null;

		if ( ! attributes ) {
			return {};
		}

		const attributeKeys = Object.keys( attributes );

		const newAttributes = {};

		attributeKeys.forEach( ( key ) => {
			const attribute = attributes[ key ];
			const source = attribute.source ?? '';

			if ( 'shortcode' === source ) {
				delete attribute.source;

				const shortcodeArgName = attribute.selector ?? attribute.attribute ?? null;
				const attributeType = attribute.type ?? 'string';
				const attributeName = attribute.attribute ?? key;

				if ( ! shortcodeArgName ) {
					return;
				}

				if ( attribute?.selector ) {
					delete attribute.selector;
				}

				attribute.shortcode = ( { named }, { shortcode } ) => {
					let shortcodeAttribute = named[ shortcodeArgName ] ?? null;
					const blockAttribute = blockAttributes[ attributeName ] ?? null;
					const blockAttributeDefault = blockAttribute?.default ?? null;
					const shortcodeContent = shortcode.content ?? '';

					if ( null === shortcodeAttribute ) {
						shortcodeAttribute = blockAttributeDefault;
					}

					if ( 'boolean' === attributeType ) {
						if ( null === shortcodeAttribute ) {
							return false;
						}

						return (
							'true' === shortcodeAttribute
							|| true === shortcodeAttribute
							|| '1' === shortcodeAttribute
							|| 1 === shortcodeAttribute
							|| 'yes' === shortcodeAttribute
							|| 'on' === shortcodeAttribute
						);
					} else if ( 'object' === attributeType ) {
						if ( null === shortcodeAttribute ) {
							return {
								label: '',
								value: '',
							};
						}

						if ( 'object' === typeof shortcodeAttribute ) {
							return shortcodeAttribute;
						}

						shortcodeAttribute = shortcodeAttribute.toString();

						return {
							label: shortcodeAttribute,
							value: shortcodeAttribute,
						};
					} else if ( 'array' === attributeType ) {
						if ( null === shortcodeAttribute ) {
							return [];
						}

						if ( Array.isArray( shortcodeAttribute ) ) {
							return shortcodeAttribute;
						}

						return shortcodeAttribute.split( ',' );
					} else if ( 'string' === attributeType ) {
						if ( null === shortcodeAttribute ) {
							return '';
						}

						return shortcodeAttribute.toString();
					} else if ( 'integer' === attributeType || 'number' === attributeType ) {
						if ( null === shortcodeAttribute ) {
							return 0;
						}

						return parseInt( shortcodeAttribute );
					} else if ( 'string_integer' === attributeType ) {
						attribute.type = 'string';

						if ( null === shortcodeAttribute ) {
							return '0';
						}

						return parseInt( shortcodeAttribute ).toString();
					} else if ( 'content' === attributeType ) {
						attribute.type = 'string';

						if ( '' !== shortcodeContent ) {
							return shortcodeContent.toString();
						}

						if ( null === shortcodeAttribute ) {
							return '';
						}

						return shortcodeAttribute.toString();
					}

					return shortcodeAttribute;
				};
			}

			newAttributes[ key ] = attribute;
		} );

		return newAttributes;
	};

	// Handle isMatch function setup.
	const transformCheckForMatch = ( isMatchConfig, shortcodeArgs ) => {
		if ( ! isMatchConfig || ! Array.isArray( isMatchConfig ) ) {
			return true;
		}

		let matches = true;

		isMatchConfig.forEach( ( matchConfig ) => {
			const shortcodeArgValue = shortcodeArgs[ matchConfig.name ] ?? null;

			if ( matchConfig?.required && ! shortcodeArgValue ) {
				matches = false;
			} else if ( matchConfig?.excluded && null !== shortcodeArgValue && undefined !== shortcodeArgValue ) {
				matches = false;
			}
		} );

		return matches;
	};

	blockArgs.attributes = createAttributesFromFields( fields );

	if ( ! blockArgs.transforms || ! blockArgs.transforms.from || [] === blockArgs.transforms.from ) {
		delete blockArgs.transforms;
	} else {
		const newTransforms = [];

		blockArgs.transforms.from.forEach( ( transform ) => {
			if ( 'shortcode' !== transform.type ) {
				newTransforms.push( transform );

				return;
			}

			const blockAttributes = blockArgs.attributes;

			transform.attributes = setupTransformAttributes( transform, blockAttributes );

			if ( ! transform?.isMatch && transform?.isMatchConfig ) {
				const isMatchConfig = transform.isMatchConfig;

				delete transform.isMatchConfig;

				// Set up the handler on transform.isMatch with what it needs.
				transform.isMatch = ( { named } ) => {
					return transformCheckForMatch( isMatchConfig, named );
				};
			}

			newTransforms.push( transform );
		} );

		blockArgs.transforms.from = newTransforms;
	}

	registerBlockType( blockName, {
		...blockArgs,
		apiVersion: 3,
		edit: createBlockEditComponent( block ),
		icon,
		save: () => null,
	} );
};

export default createBlock;
