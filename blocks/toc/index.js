import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, PanelColorSettings } from '@wordpress/block-editor';
import { PanelBody, TextControl, SelectControl, RangeControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.css';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const previewStyle = {
			'--rx-toc-text': attributes.textColor || undefined,
			'--rx-toc-bg': attributes.backgroundColor || undefined,
			'--rx-toc-font-size': attributes.fontSize ? attributes.fontSize + 'px' : undefined,
		};

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Settings', 'reader-experience' ) }>
						<TextControl
							label={ __( 'Title', 'reader-experience' ) }
							help={ __( 'Leave empty for "Contents".', 'reader-experience' ) }
							value={ attributes.title }
							onChange={ ( title ) => setAttributes( { title } ) }
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
							label={ __( 'Hide this block when there are fewer headings than this', 'reader-experience' ) }
							help={ __( 'This is a threshold to hide the whole table of contents on a short article, not a way to shorten a long list.', 'reader-experience' ) }
							min={ 1 }
							max={ 10 }
							value={ attributes.minItems }
							onChange={ ( minItems ) => setAttributes( { minItems } ) }
						/>
						<SelectControl
							label={ __( 'Style', 'reader-experience' ) }
							value={ attributes.style }
							options={ [
								{ label: __( 'Default', 'reader-experience' ), value: 'standard' },
								{ label: __( 'Compact (lighter, suited for a sidebar)', 'reader-experience' ), value: 'compact' },
							] }
							onChange={ ( style ) => setAttributes( { style } ) }
						/>
						<ToggleControl
							label={ __( 'Collapsible sub-levels per top-level heading', 'reader-experience' ) }
							help={ __( 'Useful with many headings: H3/H4 only appear after a click on their H2.', 'reader-experience' ) }
							checked={ attributes.collapseNested }
							onChange={ ( collapseNested ) => setAttributes( { collapseNested } ) }
						/>
						<RangeControl
							label={ __( 'Text size (px)', 'reader-experience' ) }
							help={ __( 'Empty (0) uses the default size of the chosen style.', 'reader-experience' ) }
							min={ 0 }
							max={ 24 }
							value={ attributes.fontSize }
							onChange={ ( fontSize ) => setAttributes( { fontSize: fontSize || 0 } ) }
						/>
					</PanelBody>
					<PanelColorSettings
						title={ __( 'Colour', 'reader-experience' ) }
						initialOpen={ false }
						colorSettings={ [
							{
								value: attributes.textColor,
								onChange: ( textColor ) => setAttributes( { textColor: textColor || '' } ),
								label: __( 'Text', 'reader-experience' ),
							},
							{
								value: attributes.backgroundColor,
								onChange: ( backgroundColor ) => setAttributes( { backgroundColor: backgroundColor || '' } ),
								label: __( 'Background', 'reader-experience' ),
							},
						] }
					/>
				</InspectorControls>
				<div
					{ ...useBlockProps( { className: 'rx-toc rx-toc--editor rx-toc--' + attributes.style, style: previewStyle } ) }
				>
					<p className="rx-toc__summary">{ attributes.title || __( 'Contents', 'reader-experience' ) }</p>
					<ol className="rx-toc__list">
						{ /* Spans, not links: a link takes the click and focus, so the block was not selected. */ }
						<li className="rx-toc__item rx-toc__item--l2">
							<span className="rx-toc__link">{ __( 'This table of contents is filled in automatically', 'reader-experience' ) }</span>
						</li>
						<li className="rx-toc__item rx-toc__item--l2">
							<span className="rx-toc__link">{ __( 'with links to the headings in the text', 'reader-experience' ) }</span>
						</li>
						<li className="rx-toc__item rx-toc__item--l2">
							<span className="rx-toc__link">{ __( 'Choose which heading levels to show in the block settings', 'reader-experience' ) }</span>
						</li>
					</ol>
				</div>
			</>
		);
	},
	save: () => null,
} );
