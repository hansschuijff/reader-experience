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
				<PanelBody title={ __( 'Settings', 'reader-experience' ) }>
					<SelectControl
						label={ __( 'Display', 'reader-experience' ) }
						help={ __( 'Determines the size and whether the explanation unfolds inline or appears in a separate panel. Both fit equally well at the bottom of the article and in the interaction bar.', 'reader-experience' ) }
						value={ attributes.variant }
						options={ [
							{ label: __( 'Large, with text (inline)', 'reader-experience' ), value: 'inline' },
							{ label: __( 'Compact, icons only (bar)', 'reader-experience' ), value: 'bar' },
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
