import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl, RangeControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import { Icon } from '../shared/icons';
import './style.css';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'reader-experience' ) }>
					<TextControl
						label={ __( 'Panel title', 'reader-experience' ) }
						help={ __( 'Leave empty for "Contents".', 'reader-experience' ) }
						value={ attributes.title }
						onChange={ ( title ) => setAttributes( { title } ) }
					/>
					<SelectControl
						label={ __( 'Side of the screen', 'reader-experience' ) }
						value={ attributes.side }
						options={ [
							{ label: __( 'Left', 'reader-experience' ), value: 'left' },
							{ label: __( 'Right', 'reader-experience' ), value: 'right' },
						] }
						onChange={ ( side ) => setAttributes( { side } ) }
					/>
					<SelectControl
						label={ __( 'Page when the panel is open', 'reader-experience' ) }
						help={ __( 'On wide screens the page can move aside to make room. Choose "Stay put" if your theme does not cope with that.', 'reader-experience' ) }
						value={ attributes.push }
						options={ [
							{ label: __( 'Move aside on wide screens', 'reader-experience' ), value: 'auto' },
							{ label: __( 'Stay put (panel floats over it)', 'reader-experience' ), value: 'never' },
						] }
						onChange={ ( push ) => setAttributes( { push } ) }
					/>
					<SelectControl
						label={ __( 'Show headings up to level', 'reader-experience' ) }
						value={ String( attributes.maxLevel ) }
						options={ [
							{ label: 'H2', value: '2' },
							{ label: __( 'H2 and H3', 'reader-experience' ), value: '3' },
							{ label: __( 'H2 to H4', 'reader-experience' ), value: '4' },
						] }
						onChange={ ( value ) => setAttributes( { maxLevel: Number( value ) } ) }
					/>
					<RangeControl
						label={ __( 'Hide this button when there are fewer headings than this', 'reader-experience' ) }
						min={ 1 }
						max={ 10 }
						value={ attributes.minItems }
						onChange={ ( minItems ) => setAttributes( { minItems } ) }
					/>
					<ToggleControl
						label={ __( 'Show characters, words and reading time', 'reader-experience' ) }
						checked={ attributes.showStats }
						onChange={ ( showStats ) => setAttributes( { showStats } ) }
					/>
					<ToggleControl
						label={ __( 'Collapsible sub-levels per top-level heading', 'reader-experience' ) }
						checked={ attributes.collapseNested }
						onChange={ ( collapseNested ) => setAttributes( { collapseNested } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps( { className: 'rx-bar__button' } ) }>
				<Icon name="toc" />
				<span className="rx-bar__label">{ __( 'Contents', 'reader-experience' ) }</span>
			</div>
		</>
	),
	save: () => null,
} );
