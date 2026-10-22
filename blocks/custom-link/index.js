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
						] }
						onChange={ ( icon ) => setAttributes( { icon } ) }
					/>
					<ToggleControl
						label={ __( 'Open in a new tab', 'reader-experience' ) }
						checked={ attributes.newTab }
						onChange={ ( newTab ) => setAttributes( { newTab } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'dwp-rx-bar__button' } ) }>
				<Icon name={ attributes.icon } />
				<span className="dwp-rx-bar__label">{ attributes.label || __( 'Link', 'reader-experience' ) }</span>
			</div>
		</>
	),
	save: () => null,
} );
