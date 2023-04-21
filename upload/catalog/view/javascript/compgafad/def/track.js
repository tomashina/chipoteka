$(document).delegate('#button-cart', 'click', function() {
	$.ajax({
		url: 'index.php?route=extension/module/compgafad/addtocart',
		async: true,
		type: 'post',
		dataType: 'json',
		data: $('#product input[type=\'text\'], #product input[type=\'hidden\'], #product input[type=\'radio\']:checked, #product input[type=\'checkbox\']:checked, #product select, #product textarea'),
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