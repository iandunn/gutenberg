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

	icon: <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M0,0h24v24H0V0z" fill="none" /><rect x="11" y="7" width="6" height="2" /><rect x="11" y="11" width="6" height="2" /><rect x="11" y="15" width="6" height="2" /><rect x="7" y="7" width="2" height="2" /><rect x="7" y="11" width="2" height="2" /><rect x="7" y="15" width="2" height="2" /><path d="M20.1,3H3.9C3.4,3,3,3.4,3,3.9v16.2C3,20.5,3.4,21,3.9,21h16.2c0.4,0,0.9-0.5,0.9-0.9V3.9C21,3.4,20.5,3,20.1,3z M19,19H5V5h14V19z" /></svg>,

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
