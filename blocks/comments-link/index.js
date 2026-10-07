import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import { Icon } from '../shared/icons';
import './style.css';

registerBlockType( metadata.name, {
	edit: () => (
		<div { ...useBlockProps( { className: 'rx-bar__button' } ) }>
			<Icon name="comments" />
			<span className="rx-bar__label">{ __( '14 reacties', 'reader-experience' ) }</span>
		</div>
	),
	save: () => null,
} );
