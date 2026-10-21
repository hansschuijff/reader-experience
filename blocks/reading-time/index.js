import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'reader-experience' ) }>
					<ToggleControl
						label={ __( 'Show the remaining time while reading', 'reader-experience' ) }
						checked={ attributes.remaining }
						onChange={ ( remaining ) => setAttributes( { remaining } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<p { ...useBlockProps() }>
				{ attributes.remaining
					? __( '~12 min left (example)', 'reader-experience' )
					: __( '12 min read (example)', 'reader-experience' ) }
			</p>
		</>
	),
	save: () => null,
} );
