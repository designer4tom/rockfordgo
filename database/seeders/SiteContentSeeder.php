<?php

namespace Database\Seeders;

use App\Models\SiteContent;
use Illuminate\Database\Seeder;

class SiteContentSeeder extends Seeder
{
    private string $locale = 'en';

    public function run(): void
    {
        // ---- SHARED: nav ----
        $this->single('shared', 'nav', [
            'brand' => 'ReadyRide',
            'label_home' => 'Home',
            'label_features' => 'Features',
            'label_safety' => 'Safety',
            'label_help' => 'Help',
            'label_about' => 'About',
        ]);

        // ---- SHARED: footer text ----
        $this->single('shared', 'footer', [
            'tagline' => 'Your trusted ride sharing partner. Fast, safe and reliable rides anytime, anywhere.',
            'copyright' => '© :year :brand. All rights reserved.',
            'col_company_title' => 'Company',
            'col_rider_title' => 'Rider',
            'col_driver_title' => 'Driver',
            'col_legal_title' => 'Legal',
        ]);

        // ---- SHARED: social links ----
        $this->single('shared', 'social', [
            'facebook' => '#',
            'twitter' => '#',
            'instagram' => '#',
            'linkedin' => '#',
        ]);

        // ---- SHARED: app store links (reused across pages) ----
        $this->single('shared', 'app_download', [
            'play_url' => 'https://play.google.com/store/apps/details?id=com.readyrider.apps&client_id=1751005720.1778910176&session_id=1779684173',
            'app_url' => 'https://testflight.apple.com/join/hRRvUR98?client_id=1751005720.1778910176&session_id=1779684173',
        ]);

        // ---- SHARED: footer columns (link lists) ----
        $this->list('shared', 'footer_col_company', [
            ['label' => 'About Us', 'url' => '/about'],
            ['label' => 'Careers', 'url' => '#'],
            ['label' => 'Blog', 'url' => '#'],
            ['label' => 'Press', 'url' => '#'],
            ['label' => 'Contact Us', 'url' => '#'],
        ]);
        $this->list('shared', 'footer_col_rider', [
            ['label' => 'How It Works', 'url' => '/home#how-it-works'],
            ['label' => 'Safety', 'url' => '/safety'],
            ['label' => 'Ride Options', 'url' => '/features'],
            ['label' => 'FAQ', 'url' => '/help'],
            ['label' => 'Customer Support', 'url' => '/help'],
        ]);
        $this->list('shared', 'footer_col_driver', [
            ['label' => 'Become a Driver', 'url' => '#'],
            ['label' => 'Driver Guide', 'url' => '#'],
            ['label' => 'Earnings', 'url' => '#'],
            ['label' => 'Safety', 'url' => '/safety'],
            ['label' => 'Driver Support', 'url' => '/help'],
        ]);
        $this->list('shared', 'footer_col_legal', [
            ['label' => 'Terms & Conditions', 'url' => '#'],
            ['label' => 'Privacy Policy', 'url' => '#'],
            ['label' => 'Cookie Policy', 'url' => '#'],
            ['label' => 'Refund Policy', 'url' => '#'],
        ]);

        // ---- HOME ----
        $this->single('home', 'hero', [
            'badge' => 'Your Ride, Your Way',
            'title_line1' => 'Get a Ride',
            'title_line2' => 'Anytime, Anywhere',
            'subtitle' => 'Book comfortable and affordable rides with trusted drivers in just a few taps.',
        ]);
        $this->list('home', 'trust_badges', [
            ['icon' => 'shield-mini.svg', 'label' => 'Safe & Secure'],
            ['icon' => 'bolt-mini.svg', 'label' => 'Quick Booking'],
            ['icon' => 'headset-mini.svg', 'label' => '24/7 Support'],
        ]);
        $this->single('home', 'features', [
            'title' => 'Why Choose ReadyRide?',
            'subtitle' => 'Enjoy the best ride experience with features designed for you.',
        ]);
        $this->list('home', 'features', [
            ['icon' => 'fare.svg', 'title' => 'Affordable Fares', 'copy' => 'Best prices for every ride. No hidden charges.'],
            ['icon' => 'shield.svg', 'title' => 'Safe & Trusted', 'copy' => 'Verified drivers and real-time trip tracking for your safety.'],
            ['icon' => 'clock.svg', 'title' => 'Quick & Easy', 'copy' => 'Book in seconds and reach your destination faster.'],
            ['icon' => 'support.svg', 'title' => '24/7 Support', 'copy' => "We're here to help you anytime, anywhere."],
        ]);
        $this->list('home', 'stats', [
            ['icon' => 'users.svg', 'value' => '1M+', 'label' => 'Happy Riders'],
            ['icon' => 'car.svg', 'value' => '10M+', 'label' => 'Rides Completed'],
            ['icon' => 'pin.svg', 'value' => '50+', 'label' => 'Cities'],
            ['icon' => 'star.svg', 'value' => '4.8', 'label' => 'User Rating'],
        ]);
        $this->single('home', 'how', [
            'title' => 'How It Works',
            'subtitle' => 'Getting your ride is simple and quick.',
        ]);
        $this->list('home', 'how', [
            ['icon' => 'pin.svg', 'title' => 'Enter Location', 'copy' => 'Enter your pickup and drop-off location.'],
            ['icon' => 'car.svg', 'title' => 'Choose a Ride', 'copy' => 'Select the ride that suits you best.'],
            ['icon' => 'card.svg', 'title' => 'Confirm & Pay', 'copy' => 'Confirm your ride and choose a payment method.'],
            ['icon' => 'flag.svg', 'title' => 'Enjoy Your Ride', 'copy' => 'Track your driver and enjoy a comfortable ride.'],
        ]);
        $this->single('home', 'cta', [
            'title' => 'Ready to Get Moving?',
            'subtitle' => 'Download the ReadyRide app and enjoy a smarter way to travel.',
        ]);

        $this->seedFeatures();
        $this->seedSafety();
        $this->seedHelp();
        $this->seedAbout();
    }

    private function seedFeatures(): void
    {
        $this->single('features', 'hero', [
            'badge' => 'Features',
            'title_line1' => 'Smart Features for',
            'title_line2' => 'Better Rides',
            'subtitle' => 'ReadyRide is built with powerful features to make every ride safe, smooth and convenient for everyone.',
        ]);
        $this->single('features', 'grid_head', [
            'title' => 'Everything You Need, All in One App',
            'subtitle' => 'Explore the powerful features that make ReadyRide your perfect travel partner.',
        ]);
        $this->list('features', 'features', [
            ['title' => 'Quick Booking', 'copy' => 'Book a ride in seconds with our simple and intuitive booking process.', 'tone' => 'rr-tone-purple', 'icon' => 'bolt'],
            ['title' => 'Safe & Secure', 'copy' => 'Verified drivers, live tracking and emergency support for your complete safety.', 'tone' => 'rr-tone-blue', 'icon' => 'shield'],
            ['title' => 'Real-time Tracking', 'copy' => 'Track your ride in real-time and share your trip details with family and friends.', 'tone' => 'rr-tone-green', 'icon' => 'pin'],
            ['title' => 'Multiple Payments', 'copy' => 'Pay your way with cash, card, mobile banking and digital wallets.', 'tone' => 'rr-tone-orange', 'icon' => 'card'],
            ['title' => '24/7 Support', 'copy' => 'Our support team is always available to help you anytime, anywhere.', 'tone' => 'rr-tone-teal', 'icon' => 'support'],
            ['title' => 'Rate & Review', 'copy' => 'Rate your driver and share your experience to help us serve you better.', 'tone' => 'rr-tone-orange', 'icon' => 'star'],
            ['title' => 'Ride History', 'copy' => 'View your past rides, invoices and download receipts whenever you need.', 'tone' => 'rr-tone-purple', 'icon' => 'document'],
            ['title' => 'Smart Notifications', 'copy' => 'Get real-time updates about your rides, offers and important alerts.', 'tone' => 'rr-tone-pink', 'icon' => 'bell'],
        ]);
        $this->list('features', 'stats', [
            ['value' => '1M+', 'label' => 'Happy Riders', 'tone' => 'rr-tone-purple', 'icon' => 'users'],
            ['value' => '10M+', 'label' => 'Rides Completed', 'tone' => 'rr-tone-green', 'icon' => 'car'],
            ['value' => '50+', 'label' => 'Cities', 'tone' => 'rr-tone-blue', 'icon' => 'pin'],
            ['value' => '4.8', 'label' => 'User Rating', 'tone' => 'rr-tone-orange', 'icon' => 'star'],
        ]);
        $this->single('features', 'cta', [
            'title' => 'Ready to Experience the Difference?',
            'subtitle' => 'Download the ReadyRide app and enjoy a smarter way to travel.',
        ]);
    }

    private function seedSafety(): void
    {
        $this->single('safety', 'hero', [
            'badge' => 'Safety First, Always',
            'title_line1' => 'Your Safety,',
            'title_line2' => 'Our Priority',
            'subtitle' => 'ReadyRide is committed to providing a safe, secure and reliable ride experience for every rider, every time.',
        ]);
        $this->single('safety', 'grid_head', [
            'title' => 'How We Keep You Safe',
            'subtitle' => 'Advanced safety features and 24/7 support to give you peace of mind.',
        ]);
        $this->list('safety', 'features', [
            ['title' => 'Verified Drivers', 'copy' => 'All drivers go through a strict verification and background-check process before joining.', 'tone' => 'tone-purple', 'icon' => 'shield'],
            ['title' => 'Real-time Tracking', 'copy' => 'Share your trip and let loved ones follow your ride live until you arrive.', 'tone' => 'tone-blue', 'icon' => 'pin'],
            ['title' => 'SOS Emergency', 'copy' => 'Tap the SOS button any time to instantly alert our team and your contacts.', 'tone' => 'tone-pink', 'icon' => 'bell'],
            ['title' => 'Ride Check', 'copy' => 'We monitor your ride for unusual stops or route changes and check in if needed.', 'tone' => 'tone-green', 'icon' => 'user'],
            ['title' => 'In-app Calling', 'copy' => 'Call your driver without sharing your personal phone number.', 'tone' => 'tone-orange', 'icon' => 'phone'],
            ['title' => 'Driver Ratings', 'copy' => 'Rate your driver after every trip to keep our community safe and accountable.', 'tone' => 'tone-purple', 'icon' => 'star'],
            ['title' => 'Data Privacy', 'copy' => 'Your personal data is encrypted and never shared without your consent.', 'tone' => 'tone-teal', 'icon' => 'lock'],
            ['title' => 'Insurance Coverage', 'copy' => 'Every ride is covered by insurance for added peace of mind.', 'tone' => 'tone-blue', 'icon' => 'shield'],
        ]);
        $this->list('safety', 'stats', [
            ['value' => '1M+', 'label' => 'Happy Riders', 'tone' => 'tone-purple', 'icon' => 'users'],
            ['value' => '10M+', 'label' => 'Rides Completed', 'tone' => 'tone-green', 'icon' => 'car'],
            ['value' => '50+', 'label' => 'Cities', 'tone' => 'tone-blue', 'icon' => 'pin'],
            ['value' => '4.8', 'label' => 'User Rating', 'tone' => 'tone-orange', 'icon' => 'star'],
        ]);
        $this->single('safety', 'tips', [
            'title' => 'Safety Tips for a Better Ride',
            'subtitle' => 'Follow these simple tips to have a safe and comfortable journey.',
        ]);
        $this->list('safety', 'tips', [
            ['tip' => 'Share your trip with loved ones', 'icon' => 'shield'],
            ['tip' => 'Verify your driver and vehicle details', 'icon' => 'users'],
            ['tip' => 'Sit in the back seat', 'icon' => 'seat'],
            ['tip' => 'Buckle up for safety', 'icon' => 'user'],
            ['tip' => 'Report any issues through the app', 'icon' => 'phone'],
        ]);
        $this->single('safety', 'cta', [
            'title' => 'Ready to Ride with Confidence?',
            'subtitle' => 'Download the ReadyRide app and enjoy a safe and secure ride experience.',
        ]);
    }

    private function seedHelp(): void
    {
        $this->single('help', 'hero', [
            'badge' => 'Help Center',
            'title_line1' => 'How can we',
            'title_line2' => 'help you?',
            'subtitle' => 'Find answers, solve issues and get the support you need.',
            'search_placeholder' => 'Search for help articles...',
        ]);
        $this->single('help', 'topics_head', [
            'title' => 'Browse Help Topics',
            'subtitle' => 'Find answers to the most common questions.',
        ]);
        $this->list('help', 'topics', [
            ['title' => 'Getting Started', 'copy' => 'Learn how to sign up, book your first ride and more.', 'tone' => 'rr-tone-purple', 'icon' => 'users'],
            ['title' => 'Ride Options', 'copy' => 'Explore ride types, pricing and availability.', 'tone' => 'rr-tone-green', 'icon' => 'car'],
            ['title' => 'Payments & Billing', 'copy' => 'Learn about payments, promos and refunds.', 'tone' => 'rr-tone-blue', 'icon' => 'card'],
            ['title' => 'Safety & Security', 'copy' => 'Your safety is our priority. Learn more about our safety features.', 'tone' => 'rr-tone-orange', 'icon' => 'shield'],
            ['title' => 'Account & Profile', 'copy' => 'Manage your account, personal info and preferences.', 'tone' => 'rr-tone-purple', 'icon' => 'user'],
            ['title' => 'App & Technical', 'copy' => 'Troubleshoot app issues and technical problems.', 'tone' => 'rr-tone-blue', 'icon' => 'chat'],
        ]);
        $this->single('help', 'faq_head', ['title' => 'Frequently Asked Questions']);
        $this->list('help', 'faq', [
            ['question' => 'How do I book a ride?', 'answer' => 'Open the app, enter your pickup and drop-off location, choose your ride type and confirm. A nearby driver will accept within minutes.'],
            ['question' => 'How can I change or cancel my ride?', 'answer' => 'You can change or cancel your ride from the My Rides screen before the driver arrives. Cancellation fees may apply.'],
            ['question' => 'What payment methods are accepted?', 'answer' => 'ReadyRide accepts credit/debit cards, mobile banking, digital wallets and cash depending on your region.'],
            ['question' => 'How do I share my trip details?', 'answer' => 'Tap the Share Trip button during your ride to send your live location and trip details to a contact.'],
            ['question' => 'What should I do if I left something in the car?', 'answer' => 'Go to your ride history, select the trip and use the contact options to reach the driver or support.'],
        ]);
        $this->single('help', 'support', [
            'title' => 'Still Need Help?',
            'subtitle' => 'Our support team is here for you 24/7.',
            'button' => 'Start a Conversation',
        ]);
        $this->list('help', 'support', [
            ['title' => 'Live Chat', 'copy' => 'Chat with our support team', 'status' => 'Online', 'icon' => 'chat'],
            ['title' => 'Email Support', 'copy' => 'support@readyride.com', 'status' => '', 'icon' => 'mail'],
            ['title' => 'Call Us', 'copy' => '+1 (800) 123-4567', 'status' => '24/7 Available', 'icon' => 'phone'],
        ]);
        $this->single('help', 'tips', [
            'title' => 'Safety Tips for a Better Ride',
            'subtitle' => 'Follow these simple tips to ensure a safe and comfortable journey.',
            'button' => 'View Safety Tips',
        ]);
        $this->list('help', 'tips', [
            ['tip' => 'Share your trip with loved ones'],
            ['tip' => 'Verify your driver and car details'],
            ['tip' => 'Sit in the back seat'],
            ['tip' => 'Report any issues through the app'],
        ]);
    }

    private function seedAbout(): void
    {
        $this->single('about', 'hero', [
            'badge' => 'About Us',
            'title_line1' => 'Driven by Purpose.',
            'title_line2' => 'Built for You.',
            'subtitle' => 'ReadyRide was founded with a simple mission: to make urban travel safer, smarter and more convenient for everyone.',
        ]);
        $this->single('about', 'mission', [
            'title' => 'Our Mission',
            'body' => 'To revolutionize the way people move by providing a reliable, affordable and safe ride-hailing experience powered by technology and care.',
        ]);
        $this->single('about', 'values_head', ['title' => 'Our Values']);
        $this->list('about', 'values', [
            ['title' => 'Safety First', 'text' => 'We prioritize your safety with verified drivers, real-time tracking and 24/7 support.', 'tone' => 'tone-purple', 'icon' => 'shield'],
            ['title' => 'Customer Focused', 'text' => 'We listen, we care and we constantly improve to deliver the best experience for our riders.', 'tone' => 'tone-green', 'icon' => 'users'],
            ['title' => 'Innovation', 'text' => 'We embrace technology and innovation to make every ride smarter and more efficient.', 'tone' => 'tone-orange', 'icon' => 'bulb'],
            ['title' => 'Trust & Integrity', 'text' => 'We believe in transparent communication, fair pricing and building lasting relationships.', 'tone' => 'tone-blue', 'icon' => 'heart'],
        ]);
        $this->list('about', 'stats', [
            ['value' => '1M+', 'label' => 'Happy Riders', 'tone' => 'tone-purple', 'icon' => 'users'],
            ['value' => '10M+', 'label' => 'Rides Completed', 'tone' => 'tone-green', 'icon' => 'car'],
            ['value' => '50+', 'label' => 'Cities', 'tone' => 'tone-blue', 'icon' => 'pin'],
            ['value' => '4.8', 'label' => 'User Rating', 'tone' => 'tone-orange', 'icon' => 'star'],
        ]);
        $this->single('about', 'team_head', [
            'title' => 'The People Behind ReadyRide',
            'subtitle' => 'A passionate team working every day to move you forward.',
        ]);
        $this->list('about', 'team', [
            ['name' => 'Rifat Hossain', 'role' => 'Founder & CEO', 'img' => 'man_image.png'],
            ['name' => 'Nusrat Jahan', 'role' => 'Head of Operations', 'img' => 'woman.png'],
            ['name' => 'Arifur Rahman', 'role' => 'CTO', 'img' => 'man_image.png'],
            ['name' => 'Tanzila Islam', 'role' => 'Head of Customer Experience', 'img' => 'woman.png'],
        ]);
        $this->single('about', 'cta', [
            'title' => 'Join Millions of Happy Riders',
            'subtitle' => 'Download the ReadyRide app and enjoy a smarter, safer way to travel - anytime, anywhere.',
        ]);
    }

    private function single(string $page, string $section, array $kv): void
    {
        foreach ($kv as $key => $value) {
            SiteContent::updateOrCreate(
                ['page' => $page, 'locale' => $this->locale, 'section' => $section, 'key' => $key],
                ['type' => 'text', 'value' => $value]
            );
        }
    }

    private function list(string $page, string $section, array $items): void
    {
        // Only seed if this list is empty (don't clobber admin edits).
        $exists = SiteContent::where('page', $page)->where('locale', $this->locale)
            ->where('section', $section)->where('key', 'item')->exists();
        if ($exists) {
            return;
        }

        foreach ($items as $i => $item) {
            SiteContent::create([
                'page' => $page, 'locale' => $this->locale, 'section' => $section,
                'key' => 'item', 'type' => 'list', 'value' => json_encode($item),
                'sort_order' => $i + 1, 'is_active' => true,
            ]);
        }
    }
}
