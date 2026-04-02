/**
 * Content carousel with extensive options to control behaviour and appearance
 * @requires https://github.com/ganlanyuan/tiny-slider
*/

const carousel = (() => {

  // forEach function
  let forEach = function (array, callback, scope) {
    for (let i = 0; i < array.length; i++) {
      callback.call(scope, i, array[i]); // passes back stuff we need
    }
  };

  let stabilizeCarousel = function (carouselElement, carouselInstance) {
    let refreshQueued = false;

    let refreshCarousel = function () {
      if (!carouselInstance || typeof carouselInstance.refresh !== 'function') return;

      try {
        carouselInstance.refresh();
      } catch (error) {
        // Ignore stale refresh attempts if the slider is being rebuilt/destroyed.
      }
    };

    let queueRefresh = function () {
      if (refreshQueued) return;

      refreshQueued = true;

      window.requestAnimationFrame(function () {
        window.requestAnimationFrame(function () {
          refreshQueued = false;
          refreshCarousel();
        });
      });
    };

    queueRefresh();
    window.addEventListener('load', queueRefresh, { once: true });
    window.addEventListener('pageshow', queueRefresh, { once: true });

    if (document.fonts && document.fonts.ready) {
      document.fonts.ready.then(queueRefresh).catch(function () {});
    }

    let carouselImages = carouselElement.querySelectorAll('img');

    forEach(carouselImages, function (index, image) {
      if (image.complete) return;

      image.addEventListener('load', queueRefresh, { once: true });
      image.addEventListener('error', queueRefresh, { once: true });
    });
  };

  // Carousel initialisation
  let carousels = document.querySelectorAll('.tns-carousel .tns-carousel-inner');
  forEach(carousels, function (index, value) {
    if (value._tnsCarousel || value.classList.contains('tns-slider')) return;

    let defaults = {
      container: value,
      controlsText: ['<i class="fal fa-chevron-left"></i>', '<i class="fal fa-chevron-right"></i>'],
      navPosition: 'bottom',
      mouseDrag: true,
      speed: 500,
      autoplayHoverPause: true,
      autoplayButtonOutput: false
    };
    let userOptions;
    if(value.dataset.carouselOptions != undefined) userOptions = JSON.parse(value.dataset.carouselOptions);
    let options = Object.assign({}, defaults, userOptions);
    let carousel = tns(options);
    value._tnsCarousel = carousel;
    stabilizeCarousel(value, carousel);
  });
})();

export default carousel;
