import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.css';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'reader-experience' ) }>
					<RangeControl
						label={ __( 'Only for articles of at least (minutes of reading)', 'reader-experience' ) }
						help={ __( 'Shorter articles are read in one go, so they get no notice and nothing is remembered.', 'reader-experience' ) }
						value={ attributes.minMinutes }
						onChange={ ( minMinutes ) => setAttributes( { minMinutes: minMinutes || 1 } ) }
						min={ 1 }
						max={ 60 }
					/>
					<RangeControl
						label={ __( 'Show the notice for (seconds)', 'reader-experience' ) }
						help={ __( '0 keeps it until the visitor chooses.', 'reader-experience' ) }
						value={ attributes.displaySeconds }
						onChange={ ( displaySeconds ) => setAttributes( { displaySeconds: displaySeconds || 0 } ) }
						min={ 0 }
						max={ 60 }
					/>
					<TextControl
						label={ __( 'Element to follow (CSS selector)', 'reader-experience' ) }
						help={ __( 'Defaults to .wp-block-post-content (block themes). With a classic theme use the class of your content container, for example .entry-content. Even without a correct value a common container is tried automatically.', 'reader-experience' ) }
						value={ attributes.target }
						onChange={ ( target ) => setAttributes( { target } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'dwp-rx-resume-preview' } ) }>
				<strong>{ __( 'Continue where you left off?', 'reader-experience' ) }</strong>
				<br />
				<span>{ __( 'From:', 'reader-experience' ) } { __( 'A heading from the article (example)', 'reader-experience' ) }</span>
				<p>{ __( 'On the site this notice appears only to visitors who return to a long article they did not finish.', 'reader-experience' ) }</p>
			</div>
		</>
	),
	save: () => null,
} );
