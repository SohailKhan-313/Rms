/**
 * RMS Menu Form Helper - Price & GST Calculation
 */

document.addEventListener("DOMContentLoaded", function () {
  const priceInput = document.getElementById("price");
  const gstInput = document.getElementById("gst");
  const totalInput = document.getElementById("total");

  if (!priceInput || !gstInput || !totalInput) return;

  function calculateTotal() {
    const price = parseFloat(priceInput.value) || 0;
    const gst = parseFloat(gstInput.value) || 0;
    const total = price + (price * gst / 100);
    totalInput.value = total.toFixed(2);
  }

  priceInput.addEventListener("input", calculateTotal);
  gstInput.addEventListener("input", calculateTotal);
});
