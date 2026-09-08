<?php

namespace Database\Seeders;

use App\Models\LandingPageContent;
use App\Models\SafetyTip;
use Illuminate\Database\Seeder;

/**
 * Realistic demo content for the in-app Help Center:
 * Safety Tips (safety_tips table) + FAQs (landing_page_contents, section=faq).
 */
class HelpContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSafetyTips();
        $this->seedFaqs();
    }

    private function seedSafetyTips(): void
    {
        $tips = [
            ['Verify your driver', 'Before you get in, match the driver\'s name, photo and vehicle number plate with what the app shows.'],
            ['Share your trip', 'Tap "Share trip" to let a family member or friend follow your ride live until you arrive.'],
            ['Wear your seatbelt', 'Always buckle up. For bike rides, make sure you wear the helmet provided by your driver.'],
            ['Use the in-app SOS', 'If you ever feel unsafe, use the SOS button in the app to alert our team and your emergency contact instantly.'],
            ['Pay & chat in-app', 'Keep payments and communication inside ReadyRide so everything stays recorded and protected.'],
            ['Check before you exit', 'Take your phone, wallet and belongings with you. You can report a lost item from your trip history.'],
        ];

        foreach ($tips as $i => [$title, $desc]) {
            SafetyTip::updateOrCreate(
                ['title' => $title],
                ['description' => $desc, 'sort_order' => $i + 1, 'is_active' => true],
            );
        }
    }

    private function seedFaqs(): void
    {
        $faqs = [
            // General
            ['general', 'What is ReadyRide?', 'ReadyRide is a ride-hailing and parcel-delivery app that connects you with nearby verified drivers for rides and same-city deliveries.'],
            ['general', 'Which areas does ReadyRide cover?', 'ReadyRide operates in supported city zones. If a driver is available near your pickup point, you can book instantly.'],
            ['general', 'How do I contact support?', 'Open the Help section in the app to chat with support, raise a complaint, or find our contact details.'],

            // Ride
            ['ride', 'How do I book a ride?', 'Enter your pickup and drop-off, choose a vehicle type, review the fare estimate and tap Book. We will match you with the nearest driver.'],
            ['ride', 'Can I schedule a ride in advance?', 'Yes. Choose "Schedule" while booking and pick a date and time. A driver is assigned as your scheduled time approaches.'],
            ['ride', 'How is the fare calculated?', 'Fares are based on distance, time, vehicle type and current demand. You always see an estimate before you confirm.'],
            ['ride', 'What if I cancel a ride?', 'You can cancel before or shortly after a driver accepts. A small cancellation fee may apply if you cancel after the grace period once the driver is on the way.'],

            // Parcel
            ['parcel', 'How do I send a parcel?', 'Choose Parcel, enter pickup and drop-off, sender and receiver details, and the parcel type, then book a delivery partner.'],
            ['parcel', 'What is Cash on Delivery (COD)?', 'With COD, the receiver pays the product amount on delivery. The driver collects it and the amount is settled to you as per platform rules.'],
            ['parcel', 'What items are not allowed?', 'Illegal, dangerous, perishable or prohibited items cannot be sent. You are responsible for the contents of your parcel.'],

            // Payment
            ['payment', 'What payment methods are supported?', 'You can pay by cash, card or your ReadyRide wallet, depending on what is enabled in your area.'],
            ['payment', 'How do I add money to my wallet?', 'Go to Wallet → Add Money, choose an amount and payment method, and complete the payment to top up instantly.'],
            ['payment', 'How do refunds work?', 'Approved refunds are credited back to your ReadyRide wallet, usually within a short time after review.'],

            // Account
            ['account', 'How do I update my profile?', 'Open Profile in the app to update your name, email and photo any time.'],
            ['account', 'How do I delete my account?', 'You can request account deletion from Profile → Account settings. Some data may be retained as required by law.'],
        ];

        // Clear existing demo FAQs to avoid duplicates, then reseed.
        LandingPageContent::where('section', 'faq')->delete();

        foreach ($faqs as $i => [$category, $question, $answer]) {
            LandingPageContent::create([
                'section' => 'faq',
                'key' => 'item',
                'value' => json_encode(compact('category', 'question', 'answer')),
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }
    }
}
