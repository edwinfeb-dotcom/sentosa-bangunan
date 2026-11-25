import Alpine from "alpinejs";
import "@phosphor-icons/web/regular";
import * as bootstrap from "bootstrap";

window.bootstrap = bootstrap;
window.Alpine = Alpine;

// Lazy-load library berat hanya saat halaman/fitur benar-benar membutuhkan
window.loadJsBarcode = async () => (await import("jsbarcode")).default;
window.loadQRCode = async () => (await import("qrcode")).default;
window.loadHtml5Qrcode = async () => (await import("html5-qrcode")).Html5Qrcode;

Alpine.start();
