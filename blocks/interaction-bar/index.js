import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
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
	edit: () => (
		<div { ...useBlockProps( { className: 'rx-bar rx-bar--editor' } ) }>
			<InnerBlocks allowedBlocks={ ALLOWED_BLOCKS } template={ TEMPLATE } orientation="horizontal" />
		</div>
	),
	save: () => (
		<div { ...useBlockProps.save( { className: 'rx-bar' } ) }>
			<div className="rx-bar__inner">
				<InnerBlocks.Content />
			</div>
		</div>
	),
} );
