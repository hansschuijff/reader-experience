import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import { Icon } from '../shared/icons';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'reader-experience' ) }>
					<SelectControl
						label={ __( 'Jump to', 'reader-experience' ) }
						value={ attributes.target }
						options={ [
							{ label: __( 'Automatic (comments if there are any, otherwise the form)', 'reader-experience' ), value: 'auto' },
							{ label: __( 'The comments', 'reader-experience' ), value: 'comments' },
							{ label: __( 'The comment form', 'reader-experience' ), value: 'respond' },
							{ label: __( 'An anchor of my own', 'reader-experience' ), value: 'custom' },
						] }
						onChange={ ( target ) => setAttributes( { target } ) }
					/>
					{ 'custom' === attributes.target && (
						<TextControl
							label={ __( 'Anchor (id)', 'reader-experience' ) }
							help={ __( 'The id of an element on the page, without the #. Falls back to the comments when empty.', 'reader-experience' ) }
							value={ attributes.anchor }
							onChange={ ( anchor ) => setAttributes( { anchor } ) }
						/>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'dwp-rx-bar__button' } ) }>
				<Icon name="comments" />
				<span className="dwp-rx-bar__label">{ __( '14 comments', 'reader-experience' ) }</span>
			</div>
		</>
	),
	save: () => null,
} );
