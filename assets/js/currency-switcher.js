jQuery(document).ready(function ($) {
    $('#currency-switcher').on('change', function () {
        var currency = $(this).val();
        var url = new URL(window.location.href);

        url.searchParams.set('currency', currency);
        url.searchParams.set('currency_switcher_nonce', tmxh_currency_nonce); // Correct nonce field name

        window.location.href = url.toString();
    });
});
