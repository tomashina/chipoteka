$(document).delegate('#button-cart, [data-quick-buy]', 'click', function() {
	$.ajax({
		url: 'index.php?route=extension/module/compgafad/addtocart',
		async: true,
		type: 'post',
		dataType: 'json',
		data: $(
		'#product .button-group-page input[type=\'text\'], #product .button-group-page input[type=\'hidden\'], #product .button-group-page input[type=\'radio\']:checked, #product .button-group-page input[type=\'checkbox\']:checked, #product .button-group-page select, #product .button-group-page textarea, ' +
		'#product .product-options input[type=\'text\'], #product .product-options input[type=\'hidden\'], #product .product-options input[type=\'radio\']:checked, #product .product-options input[type=\'checkbox\']:checked, #product .product-options select, #product .product-options textarea, ' +
		'#product select[name="recurring_id"]'
		),
		success: function(json) {
			if (json['script']) {
				$('body').append(json['script']);
			}
		}
	});
});
$(document).delegate("[onclick*='cart.add'],[onclick*='addToCart']", 'click', function() {
	var product_id = $(this).attr('onclick').match(/[0-9]+/).toString();
	var quantity = $(this).closest('.product-thumb').find("input[name*='quantity']").val();
	quantity = quantity || 1;

	$.ajax({
		url: 'index.php?route=extension/module/compgafad/addtocart',
		async: true,
		type: 'post',
		dataType: 'json',
		data: {product_id:product_id,quantity:quantity},
		success: function(json) {
			if (json['script']) {
				$('body').append(json['script']);
			}
		}
	});
});
$(document).delegate("[onclick*='wishlist.add'],[onclick*='add_to_wishlist']", 'click', function() {
	var product_id = $(this).attr('onclick').match(/[0-9]+/).toString();
	var quantity = $(this).closest('.product-thumb').find("input[name*='quantity']").val();
	quantity = quantity || 1;

	$.ajax({
		url: 'index.php?route=extension/module/compgafad/add_to_wishlist',
		async: true,
		type: 'post',
		dataType: 'json',
		data: {product_id:product_id,quantity:quantity},
		success: function(json) {
			if (json['script']) {
				$('body').append(json['script']);
			}
		}
	});
});