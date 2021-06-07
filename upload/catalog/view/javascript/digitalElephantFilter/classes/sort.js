function DigitalElephantFilterSort() {
    DigitalElephantFilterSort.selfObj = null;
    this.selector = DEFConfig.selector.sort;

    this.holdOn = function() {
<<<<<<< HEAD
        $(this.selector).attr('disabled', 'disabled');
=======
       // $(this.selector).attr('disabled', 'disabled');
>>>>>>> Admin2
    };

    this.holdOff = function() {
        $(this.selector).removeAttr('disabled');
    };
}

/**
 * Singletone
 * @return DigitalElephantFilterSort
 */
DigitalElephantFilterSort.instance = function() {
    if (this.selfObj == null) {
        this.selfObj = new DigitalElephantFilterSort();
    }

    return this.selfObj;
};