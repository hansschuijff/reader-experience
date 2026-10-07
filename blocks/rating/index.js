import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import { Icon } from '../shared/icons';
import './style.css';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Instellingen', 'reader-experience' ) }>
					<SelectControl
						label={ __( 'Weergave', 'reader-experience' ) }
						help={ __( 'Bepaalt formaat en of de toelichting inline uitklapt of in een los paneeltje verschijnt. Beide passen zowel los onderaan het artikel als in de interactiebalk.', 'reader-experience' ) }
						value={ attributes.variant }
						options={ [
							{ label: __( 'Groot, met tekst (inline)', 'reader-experience' ), value: 'inline' },
							{ label: __( 'Compact, alleen iconen (balk)', 'reader-experience' ), value: 'bar' },
						] }
						onChange={ ( variant ) => setAttributes( { variant } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'rx-rating-preview' } ) }>
				<Icon name="thumb_up" />
				<Icon name="thumb_down" />
			</div>
		</>
	),
	save: () => null,
} );
