# Realtime notification sound

The notification tone is an optional browser-side enhancement to the existing private Reverb workspace updates. Laravel persists notifications and publishes the existing `WorkspaceUpdated` event after commit. The browser plays the local tone when an authorized unread-count refresh observes an increase from the server-rendered or last-known count. Initial loads, unchanged counts, decreases, and generic workspace toasts stay silent. The shared `Marthire.notify` mechanism remains the only toast system.

The sound control is an account preference in Settings. Its persistence, authorization, browser playback behavior, migration of the former browser-local setting, and deployment requirements are documented in [Notification sound preference implementation](notification-sound-preferences.md).

The sound asset is `public/asset/marthire/sounds/notification.mp3`. Deploy it with the application assets. It is a public, non-sensitive UI asset and does not use private media storage.
