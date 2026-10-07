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
				<PanelBody title={ __( 'Instellingen', 'reader-experience' ) }>
					<TextControl
						label={ __( 'Element dat gevolgd wordt (CSS-selector)', 'reader-experience' ) }
						help={ __( 'Standaard .wp-block-post-content (FSE-thema\'s). Gebruik bij een klassiek thema de class van je content-container, bijvoorbeeld .entry-content. Ook zonder juiste waarde wordt automatisch een veelvoorkomende container geprobeerd.', 'reader-experience' ) }
						value={ attributes.target }
						onChange={ ( target ) => setAttributes( { target } ) }
					/>
				</PanelBody>
				<PanelColorSettings
					title={ __( 'Kleur', 'reader-experience' ) }
					initialOpen={ false }
					colorSettings={ [
						{
							value: attributes.color,
							onChange: ( color ) => setAttributes( { color: color || '' } ),
							label: __( 'Balkkleur', 'reader-experience' ),
						},
					] }
				/>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'rx-progress-preview' } ) }>
				<span
					className="rx-progress-preview__swatch"
					style={ attributes.color ? { background: attributes.color } : undefined }
				/>
				<span>{ __( 'Leesvoortgangsbalk (dunne balk bovenaan het scherm op de site)', 'reader-experience' ) }</span>
			</div>
		</>
	),
	save: () => null,
} );
