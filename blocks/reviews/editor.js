(function (blocks, element, blockEditor, components, serverSideRender) {
	'use strict';
	const el = element.createElement;
	const useBlockProps = blockEditor.useBlockProps;
	const InspectorControls = blockEditor.InspectorControls;
	const PanelBody = components.PanelBody;
	const SelectControl = components.SelectControl;
	const Placeholder = components.Placeholder;
	const ServerSideRender = serverSideRender;

	blocks.registerBlockType('gd6d/reviews', {
		edit: function (props) {
			const blockProps = useBlockProps();
			return el(
				element.Fragment,
				null,
				el(InspectorControls, null,
					el(PanelBody, { title: 'Réglages', initialOpen: true },
						el(SelectControl, {
							label: 'Présentation',
							value: props.attributes.layout,
							options: [
								{ label: 'Carrousel horizontal', value: 'carousel' },
								{ label: 'Grille en colonnes', value: 'grid' },
								{ label: 'Badge', value: 'badge' }
							],
							onChange: function (value) { props.setAttributes({ layout: value }); }
						}),
						el(SelectControl, {
							label: 'Langue',
							value: props.attributes.language,
							options: [ { label: 'Français', value: 'fr' }, { label: 'English', value: 'en' } ],
							onChange: function (value) { props.setAttributes({ language: value }); }
						})
					)
				),
				el('div', blockProps,
					ServerSideRender ? el(ServerSideRender, {
						block: 'gd6d/reviews',
						attributes: props.attributes,
						EmptyResponsePlaceholder: function () { return el(Placeholder, { icon: 'star-filled', label: 'Avis Google', instructions: 'Aucun avis disponible.' }); }
					}) : el(Placeholder, { icon: 'star-filled', label: 'Avis Google' })
				)
			);
		},
		save: function () { return null; }
	});
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.serverSideRender);
