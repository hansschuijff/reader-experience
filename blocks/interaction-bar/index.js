import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, useInnerBlocksProps, InnerBlocks } from '@wordpress/block-editor';
import metadata from './block.json';
import './style.css';

const ALLOWED_BLOCKS = [
	'reader-experience/rating',
	'reader-experience/comments-link',
	'reader-experience/share',
	'reader-experience/toc-open',
];

const TEMPLATE = [
	[ 'reader-experience/rating', { variant: 'bar' } ],
	[ 'reader-experience/comments-link', {} ],
	[ 'reader-experience/share', {} ],
	[ 'reader-experience/toc-open', {} ],
];

registerBlockType( metadata.name, {
	edit: () => {
		/*
		 * The wrapper itself is the container of the inner blocks (one element, not a wrapper around
		 * a second one). Otherwise the inner container covers the whole bar and a click next to the
		 * icons never reaches the bar, so the bar could not be selected by clicking it.
		 */
		const innerBlocksProps = useInnerBlocksProps( useBlockProps( { className: 'rx-bar rx-bar--editor' } ), {
			allowedBlocks: ALLOWED_BLOCKS,
			template: TEMPLATE,
			orientation: 'horizontal',
		} );
		return <div { ...innerBlocksProps } />;
	},
	save: () => (
		<div { ...useBlockProps.save( { className: 'rx-bar' } ) }>
			<div className="rx-bar__inner">
				<InnerBlocks.Content />
			</div>
		</div>
	),
} );
