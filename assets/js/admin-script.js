function copyShortcode() {
    const shortcode = document.getElementById("currency-switcher-shortcode").innerText;
    navigator.clipboard.writeText(shortcode).then(() => {
        alert("Shortcode copied to clipboard!");
    }).catch(err => {
        console.error("Error copying shortcode: ", err);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const table = document.querySelector('#manual-currency-table tbody');
    const addBtn = document.querySelector('#add-currency-row');
    const currencies = window.currencies; // Will be set by PHP
    const currencySymbols = window.currencySymbols; // Will be set by PHP

    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function updateSymbol(selectElement) {
        const selectedCurrency = selectElement.value;
        const row = selectElement.closest('tr');
        const symbolInput = row.querySelector('.currency-symbol');

        if (currencySymbols[selectedCurrency]) {
            symbolInput.value = currencySymbols[selectedCurrency];
        }
    }

    document.querySelectorAll('.currency-selector').forEach(select => {
        select.addEventListener('change', function () {
            updateSymbol(this);
        });
    });

    addBtn.addEventListener('click', function () {
        const rowIndex = Date.now();
        const newRow = document.createElement('tr');

        let currencyOptions = '';
        for (const key in currencies) {
            const label = escapeHtml(currencies[key]);
            const safeKey = escapeHtml(key);
            currencyOptions += `<option value="${safeKey}">${label}</option>`;
        }

        let gatewayCheckboxes = '';
        window.availableGateways.forEach(gateway => {
            gatewayCheckboxes += `
                <label style="display:block; margin-bottom:4px;">
                    <input type="checkbox" name="themcusw_currency_switcher_manual_rates[row${rowIndex}][gateways][]" value="${gateway.id}">
                    ${gateway.title}
                </label>
            `;
        });

        newRow.innerHTML = `
            <td>
                <select name="themcusw_currency_switcher_manual_rates[row${rowIndex}][hidden]">
                    <option value="0">No</option>
                    <option value="1">Yes</option>
                </select>
            </td>
            <td>
                <select name="themcusw_currency_switcher_manual_rates[row${rowIndex}][code]" class="currency-selector">
                    ${currencyOptions}
                </select>
            </td>
            <td>
                <select name="themcusw_currency_switcher_manual_rates[row${rowIndex}][position]">
                    <option value="left">Left $99</option>
                    <option value="right">Right 99$</option>
                </select>
            </td>
            <td><input type="text" name="themcusw_currency_switcher_manual_rates[row${rowIndex}][rate]" /></td>
            <td><input type="text" name="themcusw_currency_switcher_manual_rates[row${rowIndex}][symbol]" class="currency-symbol" /></td>
            <td>${gatewayCheckboxes}</td>
            <td><button type="button" class="button remove-currency">Remove</button></td>
        `;

        table.appendChild(newRow);

        newRow.querySelector('.currency-selector').addEventListener('change', function () {
            updateSymbol(this);
        });
    });

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-currency')) {
            e.target.closest('tr').remove();
        }
    });
});
