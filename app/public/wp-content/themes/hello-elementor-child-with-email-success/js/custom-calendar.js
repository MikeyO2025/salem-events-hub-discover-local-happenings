document.addEventListener('DOMContentLoaded', function () {
    console.log("✅ FullCalendar script loaded");

    let calendarEl = document.getElementById('event-calendar');

    if (!calendarEl) {
        console.log("🚫 Calendar div not found!");
        return;
    }

    let calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 'auto',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            console.log("📡 Fetching events via AJAX...");
            jQuery.ajax({
                url: calendar_ajax_obj.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_calendar_events'
                },
                success: function(response) {
                    console.log("✅ Events received:", response);
                    successCallback(response);
                },
                error: function(error) {
                    console.error("❌ AJAX failed", error);
                    failureCallback();
                }
            });
        },
        eventClick: function(info) {
            info.jsEvent.preventDefault();
            if (info.event.url) {
                window.open(info.event.url, '_blank');
            }
        }
    });

    calendar.render();
});
