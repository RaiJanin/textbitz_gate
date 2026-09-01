/**
 *
 * @param {'longDate'|'shortDate'|'iso'|'slashDate'|'timeOnly24'|'timeOnly12'} options
 *  Date type to use
 * - longDate = "January 6, 2025"
 * - shortDate = "Jan. 6, 2025"
 * - iso = "2025-01-06"
 * - slashDate = "01/06/2025"
 * - timeOnly24 = "14:30"
 * - timeOnly12 = "2:30pm"
 *
 * @param {string} dateString - Raw date string
 * @returns {string} - Formatted date string
 */

export function friendlyDate(options, dateString) {
    if (dateString == null || dateString == "") {
        return "";
    }

    const dateStamp = new Date(dateString);
    switch (options) {
        case "longDate":
            return dateStamp.toLocaleDateString("en-US", {
                month: "long",
                day: "numeric",
                year: "numeric",
            });

        case "shortDate":
            return dateStamp.toLocaleDateString("en-Us", {
                month: "short",
                day: "numeric",
                year: "numeric",
            });

        case "slashDate": {
            const month = String(dateStamp.getMonth() + 1).padStart(2, "0");
            const day = String(dateStamp.getDate()).padStart(2, "0");
            const year = dateStamp.getFullYear();

            return `${month} / ${day} / ${year}`;
        }

        case "iso":
            return dateStamp.toISOString().split("T")[0];

        case "timeOnly24":
            return dateStamp.toLocaleTimeString("en-US", {
                hour: "2-digit",
                minute: "2-digit",
            });

        case "timeOnly12":
            return dateStamp.toLocaleTimeString("en-US", {
                hour: "numeric",
                minute: "2-digit",
                hour12: true,
            });

        default:
            return dateStamp.toLocaleDateString();
    }
}

/**
 *
 * @param {string} dateString - Raw date string
 * @returns
 */

export function timeAgo(dateString) {
    const now = new Date();
    const past = new Date(dateString);
    const seconds = Math.floor((now - past) / 1000);
    const daySeconds = 86400;

    if (seconds > 5 * daySeconds) {
        return past.toLocaleDateString("en-US", {
            month: "short",
            day: "numeric",
            year: "numeric",
        });
    }

    const intervals = {
        day: 86400,
        hr: 3600,
        min: 60,
    };

    for (const key in intervals) {
        const interval = Math.floor(seconds / intervals[key]);
        if (interval >= 1) {
            return `${interval} ${key}${interval > 1 ? "s" : ""} ago`;
        }
    }
    return "just now";
}
