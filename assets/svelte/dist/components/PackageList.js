import * as $ from 'svelte/internal/client';
import { fade } from 'svelte/transition';
import { flip } from 'svelte/animate';

var root_3 = $.from_html(`<div class="PackageListItem"><div class="PackageListItem__icon"><img/></div> <h3 class="PackageListItem__label"><a> </a></h3></div>`);
var root_2 = $.from_html(`<div class="PackageList"></div>`);

export default function PackageList($$anchor, $$props) {
	$.push($$props, true);

	let transitionDuration = 200;
	var fragment = $.comment();
	var node = $.first_child(fragment);

	{
		var consequent = ($$anchor) => {
			var text = $.text('No packages found');

			$.append($$anchor, text);
		};

		var alternate = ($$anchor) => {
			var div = root_2();

			$.each(div, 29, () => $$props.packages, (uxPackage) => uxPackage.name, ($$anchor, uxPackage) => {
				var div_1 = root_3();
				var div_2 = $.child(div_1);
				var img = $.child(div_2);

				$.reset(div_2);

				var h3 = $.sibling(div_2, 2);
				var a = $.child(h3);
				var text_1 = $.child(a, true);

				$.reset(a);
				$.reset(h3);
				$.reset(div_1);

				$.template_effect(() => {
					$.set_style(div_2, `--gradient: ${$.get(uxPackage).gradient ?? ''};`);
					$.set_attribute(img, 'src', $.get(uxPackage).imageUrl);
					$.set_attribute(img, 'alt', `Image for the ${$.get(uxPackage).humanName ?? ''} UX package`);
					$.set_attribute(a, 'href', $.get(uxPackage).url);
					$.set_text(text_1, $.get(uxPackage).humanName);
				});

				$.animation(div_1, () => flip, () => ({ duration: transitionDuration }));
				$.transition(1, div_1, () => fade, () => ({ duration: transitionDuration }));
				$.append($$anchor, div_1);
			});

			$.reset(div);
			$.append($$anchor, div);
		};

		$.if(node, ($$render) => {
			if ($$props.packages.length === 0) $$render(consequent); else $$render(alternate, -1);
		});
	}

	$.append($$anchor, fragment);
	$.pop();
}