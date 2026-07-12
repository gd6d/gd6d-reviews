(function (blocks, element, blockEditor, components, serverSideRender) {
	'use strict';

	const el = element.createElement;
	const useBlockProps = blockEditor.useBlockProps;
	const Placeholder = components.Placeholder;
	const Spinner = components.Spinner;
	const ServerSideRender = serverSideRender;

	blocks.registerBlockType('gd6d/reviews', {
		edit: function () {
			const blockProps = useBlockProps();

			if (!ServerSideRender) {
				return el(
					'div',
					blockProps,
					el(
						Placeholder,
						{
							icon: 'star-filled',
							label: 'Avis Google'
						},
						el(Spinner)
					)
				);
			}

			return el(
				'div',
				blockProps,
				el(ServerSideRender, {
					block: 'gd6d/reviews',
					EmptyResponsePlaceholder: function () {
						return el(
							Placeholder,
							{
								icon: 'star-filled',
								label: 'Avis Google',
								instructions: 'Aucun avis disponible. Vérifie les réglages de l’extension.'
							}
						);
					}
				})
			);
		},
		save: function () {
			return null;
		}
	});
})(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.serverSideRender
);
