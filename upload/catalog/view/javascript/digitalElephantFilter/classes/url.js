/**
 * 06.10.2016.
 */
//Class digitalElephantFilter URL
function DigitalElephantFilterUrl() {
    DigitalElephantFilterUrl.selfObj = null;

    this.urlPrivate = new DigitalElephantFilterUrlPrivate();
    this.config = DEFConfig;

    this.stringToArray = function (url) {
        return this.urlPrivate.stringToArray(url);
    };

    this.cutDomainInLink = function(link) {
        return link.replace(window.location.protocol, '');
    };

    this.cutFileInLink = function(link) {
        return link.replace(/(.)*?\.php/g, '');
    };

    this.cutGetParam = function(link, name) {
        var pattern = new RegExp('(&|\\?)' + name + '.*?(?=&|$)', 'g');
        return link.replace(pattern, '');
    };

    this.getGetParam = function(link, name) {
        var pattern = new RegExp('(&|\\?)' + name + '.*?(?=&|$)', 'g');
        var result = link.match(pattern);

        //console.log(result);

        if (result === null) {
            return '';
        } else {
            var output = '';

            result.forEach(function(item, i, arr) {
                output += item.replace(/\?/, '&');
            });
            return output;
        }
    };

    this.getGetParamValue = function(link, name) {
        var $_GET_array = this.stringToArray(link);

        var output = '';
        if (typeof $_GET_array[name] !== 'undefined') {
            output = $_GET_array[name];
        }

        return output;
    };

    this.getNativeParams = function() {
        var link = window.location.href;

        var page    = this.getGetParam(link, 'page');
        var limit   = this.getGetParam(link, 'limit');
        var sort    = this.getGetParam(link, 'sort');
        var order   = this.getGetParam(link, 'order');

        return page + limit + sort + order;
    };

    this.getFilterParamsByLink = function() {
        var link = window.location.href;

        var price           = this.getGetParam(link, 'price');
        var category        = this.getGetParam(link, 'category');
        var manufacturers   = this.getGetParam(link, 'manufacturers');
        var warehouse       = this.getGetParam(link, 'warehouse');
        var option          = this.getGetParam(link, 'option');
        var attribute       = this.getGetParam(link, 'attribute');
        var actionFilter    = this.getGetParam(link, 'action_filter');
        var actionCategory  = this.getGetParam(link, 'action_category_id');
        var ajaxDEF         = this.getGetParam(link, 'ajax_digitalElephantFilter');

        return price + category + manufacturers + warehouse + option + attribute + actionFilter + actionCategory + ajaxDEF;
    };

    this.setGetParam = function(link, name, value) {
        return link + '&' + name + '=' + value;
    };

    this.removeEmptyParams = function(link) {
        this.urlPrivate.storageRemoveEmptyParams = null;
        return this.urlPrivate.removeEmptyParams(link);
    };

    this.isFilterParamsInUrl = function() {
        var link = window.location.href;
        var filterParams =
            this.getGetParam(link, 'price') +
            this.getGetParam(link, 'category') +
            this.getGetParam(link, 'manufacturers') +
            this.getGetParam(link, 'warehouse') +
            this.getGetParam(link, 'option') +
            this.getGetParam(link, 'attribute');

        filterParams = this.removeEmptyParams(filterParams);
        filterParams = (filterParams || '').replace(/^[?&]+|[?&]+$/g, '');

        return filterParams !== '';
    };

    this.changeUrlAddress = function ($_GET_string) {
        var baseUrl = this.config.action.category || window.location.pathname;
        var qs = ($_GET_string || '').toString().replace(/^[?&]+/, '');

        if (!qs) {
            window.history.pushState('', '', baseUrl);
            return;
        }

        if (baseUrl.indexOf('?') !== -1) {
            if (!/[?&]$/.test(baseUrl)) {
                baseUrl += '&';
            }
        } else {
            baseUrl += '?';
        }

        window.history.pushState('', '', baseUrl + qs);
    };
}
//Class digitalElephantFilter URL END

/**
 * Singletone
 * @return DigitalElephantFilterUrl
 */
DigitalElephantFilterUrl.instance = function() {
    if (this.selfObj == null) {
        this.selfObj = new DigitalElephantFilterUrl();
    }

    return this.selfObj;
};
