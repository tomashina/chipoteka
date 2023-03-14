(function (){
    const burger = document.querySelector('.burger');
	$('.offcanvas').on('hidden.bs.offcanvas', function () {
	    burger.classList.toggle('burger--active');
	});
	$('.offcanvas').on('shown.bs.offcanvas', function () {
	    burger.classList.toggle('burger--active');
	});
}())
