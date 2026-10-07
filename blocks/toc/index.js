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
					<PanelBody title={ __( 'Instellingen', 'reader-experience' ) }>
						<TextControl
							label={ __( 'Titel', 'reader-experience' ) }
							help={ __( 'Leeg laten voor "Inhoud".', 'reader-experience' ) }
							value={ attributes.title }
							onChange={ ( title ) => setAttributes( { title } ) }
						/>
						<SelectControl
							label={ __( 'Toon koppen tot niveau', 'reader-experience' ) }
							value={ String( attributes.maxLevel ) }
							options={ [
								{ label: 'H2', value: '2' },
								{ label: 'H2 en H3', value: '3' },
								{ label: 'H2 tot en met H4', value: '4' },
							] }
							onChange={ ( value ) => setAttributes( { maxLevel: Number( value ) } ) }
						/>
						<RangeControl
							label={ __( 'Verberg dit blok bij minder dan dit aantal koppen', 'reader-experience' ) }
							help={ __( 'Dit is een drempel om de hele inhoudsopgave te verbergen bij een kort artikel, geen manier om een lange lijst in te korten.', 'reader-experience' ) }
							min={ 1 }
							max={ 10 }
							value={ attributes.minItems }
							onChange={ ( minItems ) => setAttributes( { minItems } ) }
						/>
						<SelectControl
							label={ __( 'Stijl', 'reader-experience' ) }
							value={ attributes.style }
							options={ [
								{ label: __( 'Standaard', 'reader-experience' ), value: 'standard' },
								{ label: __( 'Compact (lichter, geschikt voor een zijbalk)', 'reader-experience' ), value: 'compact' },
							] }
							onChange={ ( style ) => setAttributes( { style } ) }
						/>
						<ToggleControl
							label={ __( 'Subniveaus inklapbaar per hoofdkop', 'reader-experience' ) }
							help={ __( 'Handig bij veel koppen: H3/H4 verschijnen dan pas na een klik op de bijbehorende H2.', 'reader-experience' ) }
							checked={ attributes.collapseNested }
							onChange={ ( collapseNested ) => setAttributes( { collapseNested } ) }
						/>
						<RangeControl
							label={ __( 'Tekstgrootte (px)', 'reader-experience' ) }
							help={ __( 'Leeg (0) gebruikt de standaardgrootte van de gekozen stijl.', 'reader-experience' ) }
							min={ 0 }
							max={ 24 }
							value={ attributes.fontSize }
							onChange={ ( fontSize ) => setAttributes( { fontSize: fontSize || 0 } ) }
						/>
					</PanelBody>
					<PanelColorSettings
						title={ __( 'Kleur', 'reader-experience' ) }
						initialOpen={ false }
						colorSettings={ [
							{
								value: attributes.textColor,
								onChange: ( textColor ) => setAttributes( { textColor: textColor || '' } ),
								label: __( 'Tekst', 'reader-experience' ),
							},
							{
								value: attributes.backgroundColor,
								onChange: ( backgroundColor ) => setAttributes( { backgroundColor: backgroundColor || '' } ),
								label: __( 'Achtergrond', 'reader-experience' ),
							},
						] }
					/>
				</InspectorControls>
				<div
					{ ...useBlockProps( { className: 'rx-toc rx-toc--' + attributes.style, style: previewStyle } ) }
				>
					<p className="rx-toc__summary">{ attributes.title || __( 'Inhoud', 'reader-experience' ) }</p>
					<ol className="rx-toc__list">
						<li className="rx-toc__item rx-toc__item--l2">
							<a href="#">{ __( 'Koppen worden hier automatisch ingevuld…', 'reader-experience' ) }</a>
						</li>
						<li className="rx-toc__item rx-toc__item--l3">
							<a href="#">{ __( '(op de site, uit de artikeltekst)', 'reader-experience' ) }</a>
						</li>
						<li className="rx-toc__item rx-toc__item--l2">
							<a href="#">…</a>
						</li>
					</ol>
				</div>
			</>
		);
	},
	save: () => null,
} );
