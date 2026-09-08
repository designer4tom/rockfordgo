# ── slf4j ─────────────────────────────────────────────────────────────
# Pulled in by the Pusher Java client. StaticLoggerBinder is an optional
# runtime binding and is safe to ignore; without this R8 fails the release
# build with "Missing class org.slf4j.impl.StaticLoggerBinder".
-dontwarn org.slf4j.**
-keep class org.slf4j.** { *; }

# ── Pusher channels ───────────────────────────────────────────────────
-dontwarn com.pusher.**
-keep class com.pusher.** { *; }

# ── Misc optional deps referenced by transitive libs ──────────────────
-dontwarn javax.annotation.**
-dontwarn javax.naming.**
