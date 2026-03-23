import * as $ from 'svelte/internal/client';
import PackageList from '../components/PackageList.js';

var root = $.from_html(`<div><input class="form-control" type="search" placeholder="This search is built in Svelte!"/> <div class="mt-3"><!></div></div>`);

export default function PackageSearch($$anchor, $$props) {
	$.push($$props, true);

	let search = $.state('');
	const filteredPackages = $.derived(() => $$props.packages.filter((uxPackage) => uxPackage.humanName.toLowerCase().includes($.get(search).toLowerCase())));
	var div = root();
	var input = $.child(div);

	$.remove_input_defaults(input);

	var div_1 = $.sibling(input, 2);
	var node = $.child(div_1);

	PackageList(node, {
		get packages() {
			return $.get(filteredPackages);
		}
	});

	$.reset(div_1);
	$.reset(div);
	$.bind_value(input, () => $.get(search), ($$value) => $.set(search, $$value));
	$.append($$anchor, div);
	$.pop();
}