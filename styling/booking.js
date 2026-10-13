(function () {
    "use strict";

    var form = document.querySelector("[data-booking-form]");
    if (!form) {
        return;
    }

    var price = Number(form.dataset.ticketPrice);
    var seatCount = form.querySelector("[data-seat-count]");
    var total = form.querySelector("[data-booking-total]");

    function updateSummary() {
        var count = form.querySelectorAll('input[name="seats[]"]:checked').length;
        seatCount.textContent = count + (count === 1 ? " seat" : " seats");
        total.textContent = (price * count) + " kr";
    }

    form.addEventListener("change", updateSummary);
    updateSummary();
})();
