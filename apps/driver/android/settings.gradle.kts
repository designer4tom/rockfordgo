pluginManagement {
    val flutterSdkPath =
        run {
            val properties = java.util.Properties()
            file("local.properties").inputStream().use { properties.load(it) }
            val flutterSdkPath = properties.getProperty("flutter.sdk")
            require(flutterSdkPath != null) { "flutter.sdk not set in local.properties" }
            flutterSdkPath
        }

    includeBuild("$flutterSdkPath/packages/flutter_tools/gradle")

    repositories {
        google()
        mavenCentral()
        gradlePluginPortal()
    }
}

plugins {
    id("dev.flutter.flutter-plugin-loader") version "1.0.0"
    id("com.android.application") version "8.11.1" apply false
    // Pinned higher than the Flutter SDK's Built-in Kotlin (2.0.0) because
    // package_info_plus / google_maps_flutter_android ship kotlin-stdlib 2.3.x
    // metadata that older compilers can't read.
    id("org.jetbrains.kotlin.android") version "2.3.0" apply false
    // Firebase — processes google-services.json so FCM tokens work.
    id("com.google.gms.google-services") version "4.4.2" apply false
}

include(":app")
