/* Administrative interface WP With Persona
 * Version 1.0
 * By Monsenhor at kobkob
 */

console.log("Begin WP With Persona admin");

(function ($) {
	// JQury loaded

	// It's the settings page
	if ($("#wp_with_persona_settings").length) {
		console.log("Administrative settings page");
		// Get all registered pages
		let pages = ["Loading...", "pages"];
		$.getJSON("/wp-json/wp/v2/posts/?", function (data) {
			const pages = data.map(function (i) {
				return i.title.rendered;
			});
			// console.log(data);
			console.log(pages);
		});
		doAutocomplete();
		$("#wpp-settings-page-add").click(function (e) {
			e.preventDefault();
			let id = $(".wpp-multitext-item").length;
			let field = $(".wpp-multitext-item").clone()[0];
			$(field).find("input").val("");
			$(field)
				.find("input")
				.attr("name", "wpwithpersona_add_page_btn" + "[" + id + "]");
			console.log("Add page.", $(field).find("input"));
			$(field).prependTo(".wpp-multitext");
			doAutocomplete();
		});
		$(".btn-remove").click(function (e) {
			e.preventDefault();
			if ($(".wpp-multitext-item").length > 1) {
				$(this).parent().detach();
				console.log($(".wpp-multitext-item").length);
			}
			console.log("Remove page.");
		});
		// List pages on autocomplete
		function doAutocomplete() {
			$(".wpp-multitext-item input").autocomplete({
				source: function (request, response) {
					$.ajax({
						type: "GET",
						url: "/wp-json/wp/v2/posts/?",
						success: function (data) {
							console.log("Search result: ", data);
							response(
								$.map(data, function (item) {
									console.log("Respponse: ", item);
									//return { label:item.title, value:item.link };
									return item.link;
								})
							);
						},
					});
				},
				select: function (e, ui) {
					//e.preventDefault();
					console.log("Selected", e, ui);
				},
			});
		}
	}
})(jQuery);
