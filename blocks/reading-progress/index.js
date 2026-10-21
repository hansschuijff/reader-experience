import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, PanelColorSettings } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.css';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'reader-experience' ) }>
					<TextControl
						label={ __( 'Element to follow (CSS selector)', 'reader-experience' ) }
						help={ __( 'Defaults to .wp-block-post-content (block themes). With a classic theme use the class of your content container, for example .entry-content. Even without a correct value a common container is tried automatically.', 'reader-experience' ) }
						value={ attributes.target }
						onChange={ ( target ) => setAttributes( { target } ) }
					/>
				</PanelBody>
				<PanelColorSettings
					title={ __( 'Colour', 'reader-experience' ) }
					initialOpen={ false }
					colorSettings={ [
						{
							value: attributes.color,
							onChange: ( color ) => setAttributes( { color: color || '' } ),
							label: __( 'Bar colour', 'reader-experience' ),
						},
					] }
				/>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'rx-progress-preview' } ) }>
				<span
					className="rx-progress-preview__swatch"
					style={ attributes.color ? { background: attributes.color } : undefined }
				/>
				<span>{ __( 'Reading progress bar (thin bar at the top of the screen on the site)', 'reader-experience' ) }</span>
			</div>
		</>
	),
	save: () => null,
} );
