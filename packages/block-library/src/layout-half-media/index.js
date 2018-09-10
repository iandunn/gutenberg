/**
 * External dependencies
 */
import { noop } from 'lodash';
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InnerBlocks,
	getColorClass,
} from '@wordpress/editor';

/**
 * Internal dependencies
 */
import edit from './edit';

export const name = 'core/half-media';

export const settings = {
	title: __( 'Half Media' ),

	icon: <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M13 17h8v-2h-8v2zM3 19h8V5H3v14zM13 9h8V7h-8v2zm0 4h8v-2h-8v2z" /></svg>,

	category: 'layout',

	attributes: {
		align: {
			type: 'string',
			default: 'wide',
		},
		backgroundColor: {
			type: 'string',
		},
		customBackgroundColor: {
			type: 'string',
		},
		mediaAlt: {
			type: 'string',
			source: 'attribute',
			selector: 'figure img',
			attribute: 'alt',
			default: '',
		},
		mediaPosition: {
			type: 'string',
			default: 'left',
		},
		mediaId: {
			type: 'number',
		},
		mediaUrl: {
			type: 'string',
			source: 'attribute',
			selector: 'figure video,figure img',
			attribute: 'src',
		},
		mediaType: {
			type: 'string',
		},
		mediaWidth: {
			type: 'number',
			source: 'attribute',
			selector: 'figure video,figure img',
			attribute: 'width',
		},
	},

	supports: {
		align: [ 'wide', 'full' ],
	},

	edit,

	save( { attributes } ) {
		const {
			backgroundColor,
			customBackgroundColor,
			mediaAlt,
			mediaPosition,
			mediaType,
			mediaUrl,
			mediaWidth,
		} = attributes;
		const mediaTypeRenders = {
			image: () => {
				return (
					<img src={ mediaUrl } alt={ mediaAlt } width={ mediaWidth } />
				);
			},
			video: () => {
				return (
					<video controls src={ mediaUrl } width={ mediaWidth } />
				);
			},
		};

		const backgroundClass = getColorClass( 'background-color', backgroundColor );
		const className = classnames( {
			'has-media-on-the-right': 'right' === mediaPosition,
			[ backgroundClass ]: backgroundClass,
		} );

		const style = {
			backgroundColor: backgroundClass ? undefined : customBackgroundColor,
		};
		return (
			<div className={ className } style={ style }>
				<figure className="wp-block-half-media__media" >
					{ ( mediaTypeRenders[ mediaType ] || noop )() }
				</figure>
				<div className="wp-block-half-media__content">
					<InnerBlocks.Content />
				</div>
			</div>
		);
	},
};
