function copyShortcode() {
    const shortcode = document.getElementById("currency-switcher-shortcode").innerText;
    navigator.clipboard.writeText(shortcode).then(() => {
        alert("Shortcode copied to clipboard!");
    }).catch(err => {
        console.error("Error copying shortcode: ", err);
    });
}