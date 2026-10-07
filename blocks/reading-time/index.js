import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Instellingen', 'reader-experience' ) }>
					<ToggleControl
						label={ __( 'Toon resterende tijd tijdens het lezen', 'reader-experience' ) }
						checked={ attributes.remaining }
						onChange={ ( remaining ) => setAttributes( { remaining } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<p { ...useBlockProps() }>
				{ attributes.remaining
					? __( 'nog ~12 min (voorbeeld)', 'reader-experience' )
					: __( '12 min leestijd (voorbeeld)', 'reader-experience' ) }
			</p>
		</>
	),
	save: () => null,
} );
