import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import { Icon } from '../shared/icons';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'reader-experience' ) }>
					<TextControl
						label={ __( 'Label', 'reader-experience' ) }
						help={ __( 'Read out by screen readers and shown as a tooltip; the bar itself shows only the icon.', 'reader-experience' ) }
						value={ attributes.label }
						onChange={ ( label ) => setAttributes( { label } ) }
					/>
					<TextControl
						label={ __( 'Address (URL)', 'reader-experience' ) }
						help={ __( 'A page, a #anchor, or a mailto: or tel: link.', 'reader-experience' ) }
						type="url"
						value={ attributes.url }
						onChange={ ( url ) => setAttributes( { url } ) }
					/>
					<SelectControl
						label={ __( 'Icon', 'reader-experience' ) }
						value={ attributes.icon }
						options={ [
							{ label: __( 'Question mark', 'reader-experience' ), value: 'help' },
							{ label: __( 'Information', 'reader-experience' ), value: 'info' },
							{ label: __( 'Home', 'reader-experience' ), value: 'home' },
							{ label: __( 'Email', 'reader-experience' ), value: 'mail' },
							{ label: __( 'Phone', 'reader-experience' ), value: 'phone' },
							{ label: __( 'Book', 'reader-experience' ), value: 'book' },
							{ label: __( 'Star', 'reader-experience' ), value: 'star' },
							{ label: __( 'Heart', 'reader-experience' ), value: 'heart' },
							{ label: __( 'Bookmark', 'reader-experience' ), value: 'bookmark' },
							{ label: __( 'Download', 'reader-experience' ), value: 'download' },
							{ label: __( 'Calendar', 'reader-experience' ), value: 'calendar' },
							{ label: __( 'Location', 'reader-experience' ), value: 'pin' },
							{ label: __( 'Person', 'reader-experience' ), value: 'user' },
							{ label: __( 'Icon font class (enter it below)', 'reader-experience' ), value: 'custom' },
						] }
						onChange={ ( icon ) => setAttributes( { icon } ) }
					/>
					{ 'custom' === attributes.icon && (
						<TextControl
							label={ __( 'Icon class', 'reader-experience' ) }
							help={ __( 'For an icon font your site already loads, for example Font Awesome: fa-solid fa-circle-question. Only letters, digits, hyphens and spaces are used.', 'reader-experience' ) }
							value={ attributes.iconClass }
							onChange={ ( iconClass ) => setAttributes( { iconClass } ) }
						/>
					) }
					<ToggleControl
						label={ __( 'Open in a new tab', 'reader-experience' ) }
						checked={ attributes.newTab }
						onChange={ ( newTab ) => setAttributes( { newTab } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'dwp-rx-bar__button' } ) }>
				{ 'custom' === attributes.icon && attributes.iconClass ? (
					<span className="dwp-rx-bar__icon">
						<i className={ attributes.iconClass.replace( /[^A-Za-z0-9_\- ]/g, '' ) } />
					</span>
				) : (
					<Icon name={ 'custom' === attributes.icon ? 'help' : attributes.icon } />
				) }
				<span className="dwp-rx-bar__label">{ attributes.label || __( 'Link', 'reader-experience' ) }</span>
			</div>
		</>
	),
	save: () => null,
} );
