/**
 *
 * @param target
 * @param value
 */
function callPlaces(target, value) {
    $.ajax({
        url: 'index.php?route=checkout/checkout/places&' + target + '=' + value,
        dataType: 'json',
        success: function(json) {
            console.log(json);

            drawPlaces(target, json);
        },
        error: function(xhr, ajaxOptions, thrownError) {
            console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        }
    });
}

/**
 *
 * @param target
 * @param json
 */
function drawPlaces(target, json) {
    let html = '';
    let naziv = '';
    let link = '';
    let obj = Object.keys(json);

    console.log(target)

    for (let i = 0; i < obj.length; i++) {
        console.log(json[obj[i]].cityname);

        if (target == 'city') {
            naziv = '<strong>' + json[obj[i]].cityname + '</strong> ' + json[obj[i]].zipcode;
        } else {
            naziv = '<strong>' + json[obj[i]].zipcode + '</strong> ' + json[obj[i]].cityname;
        }

        link = "selectPlace('" + json[obj[i]].cityname + "', '" + json[obj[i]].zipcode + "');";

        if (i == (obj.length - 1)) {
            html += '<button href="javascript:void(0);" onclick="' + link + '" class="dropdown-item">' + naziv + '</button>';
        } else {
            html += '<button href="javascript:void(0);" onclick="' + link + '" class="dropdown-item">' + naziv + '</button><div class="dropdown-divider"></div>';
        }
    }

    document.getElementById('payment-' + target + '-drop').style['display'] = 'block';
    $('#payment-' + target + '-drop').html(html);
}

/**
 *
 * @param naziv
 * @param zip
 */
function selectPlace(naziv, zip) {
    console.log(naziv, zip)

    $('#input-payment-city').val(naziv);
    $('#input-payment-postcode').val(zip);
}