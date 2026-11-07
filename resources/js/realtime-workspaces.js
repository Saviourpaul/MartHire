const allowedTopics = new Set([
    "users",
    "organizations",
    "jobs",
    "candidates",
    "applications",
    "team",
    "interviews",
    "notifications",
]);
const allowedTypes = new Set(["success", "error", "warning", "info"]);

/** Bridge private Reverb invalidations into the existing LiveTables + toast flow. */
export function initializeRealtimeWorkspaces({
    window: win = window,
    document: doc = document,
    fetch: fetcher = fetch,
    notificationSound = null,
} = {}) {
    const echo = win.Echo;
    const body = doc.body;
    const initialCount = Number(
        doc.querySelector?.('[data-notification-count]')?.dataset?.notificationCountValue,
    );
    let knownNotificationCount =
        Number.isSafeInteger(initialCount) && initialCount >= 0 ? initialCount : null;
    let activeCountRefresh = null;
    let realtimeCountCheckPending = false;

    const refreshNotificationCount = async ({ source = 'background' } = {}) => {
        const url = body?.dataset.notificationCountUrl;
        if (!url) return;

        if (source === 'reverb') realtimeCountCheckPending = true;
        if (activeCountRefresh) return activeCountRefresh;

        realtimeCountCheckPending = false;
        let retryRealtimeCheck = false;

        activeCountRefresh = (async () => {
            try {
                const response = await fetcher(url, {
                    credentials: "same-origin",
                    cache: "no-store",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                });
                if (!response.ok) return;
                const payload = await response.json();
                const count = Number(payload?.count);
                if (!Number.isSafeInteger(count) || count < 0) return;

                const previousCount = knownNotificationCount;
                const realtimeCheckArrivedDuringRequest = realtimeCountCheckPending;

                doc.querySelectorAll("[data-notification-count]").forEach(
                    (badge) => {
                        badge.dataset.notificationCountValue = String(count);
                        badge.textContent = count > 99 ? "99+" : String(count);
                        badge.hidden = count === 0;
                        badge.setAttribute(
                            "aria-label",
                            count === 1
                                ? "1 unread notification"
                                : `${count} unread notifications`,
                        );
                    },
                );
                doc.querySelectorAll("[data-notification-link]").forEach((link) => {
                    link.setAttribute(
                        "aria-label",
                        count === 0
                            ? link.dataset.notificationLabel || "Notifications"
                            : count === 1
                              ? "1 unread notification"
                              : `${count} unread notifications`,
                    );
                });

                knownNotificationCount = count;

                const countIncreased = previousCount !== null && count > previousCount;
                if (countIncreased) {
                    realtimeCountCheckPending = false;
                    void notificationSound?.play?.();
                } else if (realtimeCheckArrivedDuringRequest) {
                    // The request may have started just before the database commit.
                    // Retry once after it finishes so the committed count can arrive.
                    retryRealtimeCheck = true;
                }
            } catch (_) {
                // The stored inbox and the normal page refresh remain available.
            }
        })();

        try {
            await activeCountRefresh;
        } finally {
            activeCountRefresh = null;
        }

        if (retryRealtimeCheck) {
            realtimeCountCheckPending = false;
            return refreshNotificationCount({ source: 'reverb' });
        }

        if (realtimeCountCheckPending) {
            return refreshNotificationCount({ source: 'reverb' });
        }
    };

    const onDataChanged = (event) => {
        if (event.detail?.topics?.includes("notifications"))
            refreshNotificationCount({
                source: event.detail?.source === 'reverb' ? 'reverb' : 'background',
            });
    };
    const onVisibilityChange = () => {
        if (!doc.hidden && win.navigator?.onLine !== false)
            refreshNotificationCount();
    };
    const onOnline = () => refreshNotificationCount();
    win.addEventListener("nw:data-changed", onDataChanged);
    doc.addEventListener?.("visibilitychange", onVisibilityChange);
    win.addEventListener("online", onOnline);

    // Keep the header count fresh if Reverb disconnects or an event is missed.
    // Poll only while visible; the WebSocket remains the immediate update path.
    const fallbackPoll = body?.dataset.notificationCountUrl
        ? win.setInterval?.(() => {
              if (
                  !doc.hidden &&
                  win.navigator?.onLine !== false
              ) {
                  refreshNotificationCount();
              }
          }, 60000) ?? null
        : null;
    refreshNotificationCount();

    const subscriptions = [];
    const teardown = () => {
        subscriptions.forEach(([name]) =>
            echo?.leave?.(`private-${name}`),
        );
        win.removeEventListener?.("nw:data-changed", onDataChanged);
        win.removeEventListener?.("online", onOnline);
        doc.removeEventListener?.("visibilitychange", onVisibilityChange);
        if (fallbackPoll !== null) win.clearInterval?.(fallbackPoll);
    };

    if (!echo) return teardown;

    const onUpdate = (event) => {
        const topics = Array.isArray(event?.topics)
            ? [
                  ...new Set(
                      event.topics.filter((topic) => allowedTopics.has(topic)),
                  ),
              ]
            : [];

        if (topics.length) {
            const EventClass = win.CustomEvent ?? CustomEvent;
            win.dispatchEvent(
                new EventClass("nw:data-changed", {
                    detail: { topics, source: "reverb" },
                }),
            );
        }

        if (typeof event?.message === "string" && event.message.trim()) {
            const type = allowedTypes.has(event.type) ? event.type : "info";
            win.Marthire?.notify(event.message, { type });
        }
    };

    if (body?.dataset.workspaceRole === "admin") {
        const channel = echo.private("admins");
        channel.listen(".workspace.updated", onUpdate);
        subscriptions.push(["admins", channel]);
    }

    const organizationId = Number(body?.dataset.organizationId || 0);
    if (
        body?.dataset.workspaceRole === "organization" &&
        Number.isSafeInteger(organizationId) &&
        organizationId > 0
    ) {
        const name = `organizations.${organizationId}`;
        const channel = echo.private(name);
        channel.listen(".workspace.updated", onUpdate);
        subscriptions.push([name, channel]);
    }

    const workspaceUserId = Number(body?.dataset.workspaceUserId || 0);
    if (
        body?.dataset.workspaceRole === "applicant" &&
        Number.isSafeInteger(workspaceUserId) &&
        workspaceUserId > 0
    ) {
        const name = `App.Models.User.${workspaceUserId}`;
        const channel = echo.private(name);
        channel.listen(".workspace.updated", onUpdate);
        subscriptions.push([name, channel]);
    }

    return teardown;
}
