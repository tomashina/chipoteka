$(document).ready(function() {
//Google Analytics Expert Events
	$("button").on("click", function(){
		if ($(this).attr("onclick")) {
			var type = $(this).attr("onclick").split('(')[0];
			 
			if (type.match(/^(cart.add|wishlist.add|compare.add)$/)) {
				var action = ({
					"cart.add"  : "Add To Cart",
					"wishlist.add" : "Add To Wishlist",
					"compare.add" : "Add To Compare"
				})[type];
			} else {
				var action = null;
			}
		} else {
			var action = ($(this).attr("id") == 'button-cart') ? $(this).text() : null;
		}	
			
		var module = $(this).parent().siblings('.caption').find('h4').text();
		var product = $(this).parents("#content").find('h1').text();

		var label = module ? module : (product ? product : '');
		var route = getURLVar('route').split('/')[1];
		var page = route.charAt(0).toUpperCase() + route.slice(1).toLowerCase();
		var category = module ? route == 'product' ? 'Related ' + page : page : page;
		
		if (category && action && label) {
			ga('send', 'event', category, action, label);
		}
		
	});
	
//Google Analytics Expert Checkout Funnel	
	$(document).delegate('[id^=button-]', 'click', function() {
		var route = $(this).attr("id");
		var step = route.substring(route.indexOf('-') + 1)
		if (step.match(/^(login|account)$/)) {
			var step = 'checkout-options';
		} else if (step.match(/^(register|guest)$/)) {
			var step = 'payment-address';
		} else if (step.match(/^(register|guest-shipping)$/)) {
			var step = 'shipping-address';
		}		

		if (step.match(/^(checkout-options|payment-address|shipping-address|shipping-method|payment-method|confirm)$/)) {
			ga('send', 'pageview', '/gae/' + step);
		}
	});
});