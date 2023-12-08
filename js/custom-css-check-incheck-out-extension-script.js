jQuery(document).ready(function($) {
    var roomTypeIdOnPage = $('.mphb-calendar').data('room-type-id');
    
    // Function to apply your class to the relevant dates.
    function applyClassToDates() {
        $.each(bookingData.dates, function(index, dateInfo) {
            if (String(dateInfo.room_type_id) === String(roomTypeIdOnPage)) {
                var date = new Date(dateInfo.check_in_date + 'T12:00:00');
                var timestamp = date.getTime();
                var dateClass = '.dp' + timestamp + '.mphb-booked-date';
                $(dateClass).addClass('check-out-check-in-customized');
            }
        });
    }

    // Apply the class when the page loads.
    setTimeout(function() {
        applyClassToDates();
    }, 1000);

    // Set up a MutationObserver to reapply the class when the calendar changes.
    var observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                applyClassToDates();
            }
        });
    });

    // Observe the calendar for changes to its child elements.
    observer.observe($('.mphb-calendar')[0], { childList: true, subtree: true });
});
