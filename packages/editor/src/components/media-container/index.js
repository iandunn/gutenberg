/**
 * External dependencies
 */
import ResizableBox from 're-resizable';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Component, Fragment } from '@wordpress/element';
import { IconButton, Toolbar } from '@wordpress/components';

/**
 * Internal dependencies
 */
import BlockControls from '../block-controls';
import MediaPlaceholder from '../media-placeholder';
import MediaUpload from '../media-upload';

class MediaContainer extends Component {
	renderToolbarEditButton() {
		const { mediaId, onSelectMedia } = this.props;
		return (
			<BlockControls>
				<Toolbar>
					<MediaUpload
						onSelect={ onSelectMedia }
						type="image/*,video/*"
						value={ mediaId }
						render={ ( { open } ) => (
							<IconButton
								className="components-toolbar__control"
								label={ __( 'Edit Media' ) }
								icon="edit"
								onClick={ open }
							/>
						) }
					/>
				</Toolbar>
			</BlockControls>
		);
	}

	renderImage() {
		const { mediaAlt, mediaUrl, className } = this.props;
		return (
			<Fragment>
				{ this.renderToolbarEditButton() }
				<figure className={ className }>
					<img src={ mediaUrl } alt={ mediaAlt } />
				</figure>
			</Fragment>
		);
	}

	renderVideo() {
		const { mediaUrl, className } = this.props;
		return (
			<Fragment>
				{ this.renderToolbarEditButton() }
				<figure className={ className }>
					<video controls src={ mediaUrl } />
				</figure>
			</Fragment>
		);
	}

	renderPlaceholder() {
		const { onSelectMedia, className } = this.props;
		return (
			<MediaPlaceholder
				icon="format-image"
				labels={ {
					title: __( 'Media area' ),
					name: __( 'a media file (image or video)' ),
				} }
				className={ className }
				onSelect={ onSelectMedia }
				accept="image/*,video/*"
				type="image,video"
			/>
		);
	}

	render() {
		const { maxWidth, mediaPosition, mediaUrl, mediaType, mediaWidth, onWidthChange } = this.props;
		if ( mediaType && mediaUrl ) {
			const handleClasses = {
				left: 'editor-media-container__resize-handler',
				right: 'editor-media-container__resize-handler',
			};
			const onResizeStop = ( event, direction, elt, delta ) => {
				onWidthChange( mediaWidth + delta.width );
			};
			const enablePositions = {
				right: mediaPosition === 'left',
				left: mediaPosition === 'right',
			};

			let mediaElement = null;
			switch ( mediaType ) {
				case 'image':
					mediaElement = this.renderImage();
					break;
				case 'video':
					mediaElement = this.renderVideo();
					break;
			}
			return (
				<ResizableBox
					className="editor-media-container__resizer"
					size={ { width: mediaWidth } }
					minWidth="10"
					maxWidth={ maxWidth }
					handleClasses={ handleClasses }
					enable={ enablePositions }
					onResizeStop={ onResizeStop }
					axis="x"
				>
					{ mediaElement }
				</ResizableBox>
			);
		}
		return this.renderPlaceholder();
	}
}

export default MediaContainer;
