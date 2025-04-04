document.addEventListener('DOMContentLoaded', function () {
    console.log("✅ FullCalendar script loaded");

    const calendarEl = document.getElementById('event-calendar');
    const typeFilter = document.getElementById('filter-type');
    const categoryFilter = document.getElementById('filter-category');

    if (!calendarEl) {
        console.log("🚫 Calendar div not found!");
        return;
    }

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 'auto',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            console.log("📡 Fetching events via AJAX...");

            const postData = {
                action: 'get_calendar_events',
            };

            if (typeFilter && typeFilter.value) {
                postData.event_type = typeFilter.value;
            }

            if (categoryFilter && categoryFilter.value) {
                postData.event_category = categoryFilter.value;
            }

            jQuery.ajax({
                url: calendar_ajax_obj.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: postData,
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

    // Load taxonomy terms for dropdowns
    function loadTaxonomy(endpoint, dropdown) {
        fetch(endpoint)
            .then(res => res.json())
            .then(data => {
                if (Array.isArray(data)) {
                    data.forEach(term => {
                        const opt = document.createElement("option");
                        opt.value = term.slug;
                        opt.textContent = term.name;
                        dropdown.appendChild(opt);
                    });
                }
            })
            .catch(err => console.error("❌ Taxonomy fetch error:", err));
    }

    if (typeFilter) {
        loadTaxonomy('/wp-json/wp/v2/event_listing_type', typeFilter);
        typeFilter.addEventListener('change', () => calendar.refetchEvents());
    }
    
    if (categoryFilter) {
        loadTaxonomy('/wp-json/wp/v2/event_listing_category', categoryFilter);
        categoryFilter.addEventListener('change', () => calendar.refetchEvents());
    }
    

    calendar.render();
});