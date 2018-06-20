/**
 * External dependencies
 */
import classnames from 'classnames';
import { get } from 'lodash';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	BlockControls,
	InnerBlocks,
	InspectorControls,
	PanelColorSettings,
	withColors,
	MediaContainer,
} from '@wordpress/editor';
import { Component, Fragment } from '@wordpress/element';
import { Toolbar } from '@wordpress/components';

/**
 * Constants
 */
const ALLOWED_BLOCKS = [ 'core/button', 'core/paragraph', 'core/heading', 'core/list' ];
const TEMPLATE = [
	[ 'core/paragraph', { fontSize: 'large', placeholder: 'Content…' } ],
];
const MAX_MEDIA_WIDTH = 900;

class ImageEdit extends Component {
	constructor() {
		super( ...arguments );

		this.onSelectMedia = this.onSelectMedia.bind( this );
		this.onWidthChange = this.onWidthChange.bind( this );
	}

	onSelectMedia( media ) {
		const { setAttributes } = this.props;
		let newMediaWidth;
		if ( media.width ) {
			newMediaWidth = parseInt( media.width );
		} else {
			const fullSizeWidth = get( media, [ 'sizes', 'full', 'width' ] );
			if ( fullSizeWidth ) {
				newMediaWidth = parseInt( fullSizeWidth );
			}
		}

		const mediaWidthProp = Number.isFinite( newMediaWidth ) ?
			{ mediaWidth: Math.min( newMediaWidth, MAX_MEDIA_WIDTH ) } :
			{};

		let mediaType;
		// for media selections originated from a file upload.
		if ( media.media_type ) {
			if ( media.media_type === 'image' ) {
				mediaType = 'image';
			} else {
				// only images and videos are accepted so if the media_type is not an image we can assume it is a video.
				// video contain the media type of 'file' in the object returned from the rest api.
				mediaType = 'video';
			}
		} else { // for media selections originated from existing files in the media library.
			mediaType = media.type;
		}

		setAttributes( {
			mediaAlt: media.alt,
			mediaId: media.id,
			mediaType,
			mediaUrl: media.url,
			mediaWidth: newMediaWidth,
			...mediaWidthProp,
		} );
	}

	onWidthChange( width ) {
		const { setAttributes } = this.props;

		setAttributes( {
			mediaWidth: width,
		} );
	}

	renderMediaArea() {
		const { attributes } = this.props;
		const { mediaAlt, mediaId, mediaPosition, mediaType, mediaUrl, mediaWidth } = attributes;

		return (
			<MediaContainer
				maxWidth={ MAX_MEDIA_WIDTH }
				className="block-library-half-media__media-container"
				onSelectMedia={ this.onSelectMedia }
				onWidthChange={ this.onWidthChange }
				{ ...{ mediaAlt, mediaId, mediaType, mediaUrl, mediaPosition, mediaWidth } }
			/>
		);
	}

	render() {
		const { attributes, backgroundColor, setAttributes, setBackgroundColor } = this.props;
		const { mediaPosition } = attributes;
		const className = classnames( 'wp-block-half-media', {
			'has-media-on-the-right': 'right' === mediaPosition,
			[ backgroundColor.class ]: backgroundColor.class,
		} );
		const style = {
			backgroundColor: backgroundColor.value,
		};
		const colorSettings = [ {
			value: backgroundColor.value,
			onChange: setBackgroundColor,
			label: __( 'Background Color' ),
		} ];
		const toolbarControls = [ {
			icon: 'align-left',
			title: __( 'Show media on left' ),
			isActive: mediaPosition === 'left',
			onClick: () => setAttributes( { mediaPosition: 'left' } ),
		}, {
			icon: 'align-left',
			title: __( 'Show media on right' ),
			isActive: mediaPosition === 'right',
			onClick: () => setAttributes( { mediaPosition: 'right' } ),
		} ];
		return (
			<Fragment>
				<InspectorControls>
					<PanelColorSettings
						title={ __( 'Color Settings' ) }
						initialOpen={ false }
						colorSettings={ colorSettings }
					/>
				</InspectorControls>
				<BlockControls>
					<Toolbar
						controls={ toolbarControls }
					/>
				</BlockControls>
				<div className={ className } style={ style } >
					{ this.renderMediaArea() }
					<InnerBlocks
						allowedBlocks={ ALLOWED_BLOCKS }
						template={ TEMPLATE }
					/>
				</div>
			</Fragment>
		);
	}
}

export default withColors( 'backgroundColor' )( ImageEdit );
