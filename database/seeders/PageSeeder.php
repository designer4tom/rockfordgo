<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $p) {
            Page::updateOrCreate(
                ['slug' => $p['slug'], 'app_type' => $p['app_type']],
                ['title' => $p['title'], 'content' => $p['content'], 'is_active' => true],
            );
        }
    }

    private function pages(): array
    {
        return [
            ['slug' => 'privacy-policy', 'app_type' => 'customer', 'title' => 'Privacy Policy', 'content' => $this->customerPrivacy()],
            ['slug' => 'privacy-policy', 'app_type' => 'driver', 'title' => 'Privacy Policy', 'content' => $this->driverPrivacy()],
            ['slug' => 'terms-conditions', 'app_type' => 'customer', 'title' => 'Terms & Conditions', 'content' => $this->customerTerms()],
            ['slug' => 'terms-conditions', 'app_type' => 'driver', 'title' => 'Terms & Conditions', 'content' => $this->driverTerms()],
            ['slug' => 'about-us', 'app_type' => 'common', 'title' => 'About Us', 'content' => $this->about()],
        ];
    }

    private function customerPrivacy(): string
    {
        return <<<'HTML'
<h2>Privacy Policy</h2>
<p><em>Last updated: June 2026</em></p>
<p>ReadyRide ("we", "us") values your privacy. This policy explains what information we collect when you use the ReadyRide customer app and how we use and protect it.</p>

<h3>1. Information We Collect</h3>
<ul>
<li><strong>Account details</strong> — your name, phone number, email and profile photo.</li>
<li><strong>Location</strong> — your pickup and drop-off locations to match you with nearby drivers and enable live trip tracking.</li>
<li><strong>Trip & payment data</strong> — ride/parcel history, fares, tips, wallet transactions and payment method details.</li>
<li><strong>Device info</strong> — device model, OS version and push-notification token.</li>
</ul>

<h3>2. How We Use Your Information</h3>
<ul>
<li>To request, match, track and complete your rides and parcel deliveries.</li>
<li>To process payments, refunds, wallet top-ups and receipts.</li>
<li>To provide customer support and resolve disputes.</li>
<li>To send trip updates, promotions and important account notices.</li>
<li>To keep the platform safe and prevent fraud.</li>
</ul>

<h3>3. Sharing Your Information</h3>
<p>We share only what is necessary to complete your trip — for example, your name, pickup point and contact number are shared with the assigned driver. We never sell your personal data. We may share data with payment processors and as required by law.</p>

<h3>4. Location Data</h3>
<p>Location is used while you have an active or recent trip. You can disable location from your device settings, but core features such as booking and live tracking will not work without it.</p>

<h3>5. Data Security & Retention</h3>
<p>We use industry-standard safeguards to protect your data and retain it only as long as needed for the services and legal requirements.</p>

<h3>6. Your Rights</h3>
<p>You may view or update your profile in the app, request account deletion, or contact our support team for any privacy request.</p>

<h3>7. Contact Us</h3>
<p>For any privacy question, reach our support team from the Help section of the app.</p>
HTML;
    }

    private function driverPrivacy(): string
    {
        return <<<'HTML'
<h2>Privacy Policy</h2>
<p><em>Last updated: June 2026</em></p>
<p>This policy explains how ReadyRide collects and uses information from drivers and delivery partners using the ReadyRide driver app.</p>

<h3>1. Information We Collect</h3>
<ul>
<li><strong>Identity & documents</strong> — name, phone, photo, NID, driving licence, vehicle registration and insurance.</li>
<li><strong>Vehicle details</strong> — make, model, category and registration number.</li>
<li><strong>Live location</strong> — continuously while you are online, to receive nearby requests and let customers track active trips.</li>
<li><strong>Earnings & wallet data</strong> — trip earnings, commissions, dues, recharges and withdrawals.</li>
<li><strong>Performance data</strong> — ratings, acceptance and completion of trips.</li>
</ul>

<h3>2. How We Use Your Information</h3>
<ul>
<li>To verify your eligibility and approve your account.</li>
<li>To match you with nearby ride and parcel requests.</li>
<li>To calculate earnings, commission, dues and process withdrawals.</li>
<li>To maintain quality, safety and fraud prevention on the platform.</li>
</ul>

<h3>3. Location While Online</h3>
<p>Your location is collected only while you are online. When you go offline, location sharing stops. Accurate location is required to receive trip requests.</p>

<h3>4. Sharing</h3>
<p>Your name, photo, vehicle details and live location are shared with the customer for an active trip. Document data is used for verification and is not shared publicly. We never sell your data.</p>

<h3>5. Document Retention</h3>
<p>Verification documents are stored securely and retained as required for compliance and dispute resolution.</p>

<h3>6. Your Rights</h3>
<p>You can update your documents and profile in the app and request account deletion subject to settlement of any pending dues.</p>

<h3>7. Contact Us</h3>
<p>For privacy questions, contact support from the Help section of the driver app.</p>
HTML;
    }

    private function customerTerms(): string
    {
        return <<<'HTML'
<h2>Terms &amp; Conditions</h2>
<p><em>Last updated: June 2026</em></p>
<p>By using the ReadyRide customer app you agree to these terms. Please read them carefully.</p>

<h3>1. Using ReadyRide</h3>
<p>ReadyRide is a technology platform that connects you with independent drivers for rides and parcel delivery. You must be at least 18 years old and provide accurate account information.</p>

<h3>2. Bookings</h3>
<ul>
<li>Fares are shown as an estimate before booking and may change with distance, time, demand (surge) and waiting.</li>
<li>You are responsible for providing the correct pickup, drop-off and parcel details.</li>
<li>Scheduled bookings are subject to driver availability at the scheduled time.</li>
</ul>

<h3>3. Payments &amp; Wallet</h3>
<ul>
<li>You can pay by cash, card or wallet as enabled in the app.</li>
<li>Wallet top-ups are non-transferable and used only for ReadyRide services.</li>
<li>Receipts are available in your trip history.</li>
</ul>

<h3>4. Cancellations</h3>
<p>You may cancel a request before or shortly after a driver accepts. A cancellation fee may apply if you cancel after the grace period once a driver is on the way.</p>

<h3>5. Conduct &amp; Safety</h3>
<p>Treat drivers with respect. Illegal, dangerous or abusive behaviour, and carrying prohibited items in parcels, are strictly forbidden and may lead to account suspension.</p>

<h3>6. Ratings &amp; Disputes</h3>
<p>You can rate each trip and raise a complaint from the Help section. We review disputes fairly and may issue refunds where appropriate.</p>

<h3>7. Limitation of Liability</h3>
<p>ReadyRide provides the platform "as is" and is not liable for the conduct of independent drivers beyond what the law requires.</p>

<h3>8. Changes</h3>
<p>We may update these terms from time to time. Continued use of the app means you accept the updated terms.</p>
HTML;
    }

    private function driverTerms(): string
    {
        return <<<'HTML'
<h2>Terms &amp; Conditions</h2>
<p><em>Last updated: June 2026</em></p>
<p>These terms govern your use of the ReadyRide driver app as an independent driver or delivery partner.</p>

<h3>1. Eligibility &amp; Verification</h3>
<ul>
<li>You must hold a valid driving licence, vehicle registration and insurance, and submit them for verification.</li>
<li>Your account becomes active only after admin approval.</li>
<li>You must keep your documents up to date; expired documents prevent you from going online.</li>
</ul>

<h3>2. Accepting Trips</h3>
<ul>
<li>You receive nearby requests while online and may accept or decline them.</li>
<li>Repeatedly ignoring or cancelling accepted trips may affect your standing on the platform.</li>
</ul>

<h3>3. Earnings, Commission &amp; Dues</h3>
<ul>
<li>You earn the trip fare minus the platform commission.</li>
<li>For cash trips, the commission may be recorded as "due" and recovered from your wallet or recharge.</li>
<li>If your due exceeds the allowed limit, you cannot go online until it is cleared.</li>
<li>Withdrawals are processed to your registered account after admin approval.</li>
</ul>

<h3>4. Cash on Delivery (Parcel)</h3>
<p>For COD parcels you must have sufficient wallet balance to cover the product value, collect the correct amount, and remit it as per platform rules.</p>

<h3>5. Conduct &amp; Safety</h3>
<p>Drive safely, follow traffic laws, behave professionally and never operate under the influence. Misconduct may lead to suspension or removal.</p>

<h3>6. Ratings</h3>
<p>Customers rate your trips. Consistently low ratings may affect your account status.</p>

<h3>7. Account Suspension</h3>
<p>We may suspend or terminate accounts for fraud, safety violations, fake trips or repeated policy breaches.</p>

<h3>8. Changes</h3>
<p>We may update these terms. Continued use of the driver app means you accept the updated terms.</p>
HTML;
    }

    private function about(): string
    {
        return <<<'HTML'
<h2>About ReadyRide</h2>
<p>ReadyRide is your everyday ride-hailing and parcel-delivery companion. Whether you need a quick bike ride, a comfortable car, or want to send a package across the city, ReadyRide connects you with trusted nearby drivers in just a few taps.</p>

<h3>Our Mission</h3>
<p>To make city travel and delivery safe, affordable and reliable for everyone — while creating flexible earning opportunities for drivers.</p>

<h3>What We Offer</h3>
<ul>
<li><strong>Rides</strong> — bike, car and more, with upfront fares and live tracking.</li>
<li><strong>Parcel delivery</strong> — fast same-city delivery with cash-on-delivery support.</li>
<li><strong>Safety first</strong> — verified drivers, live trip sharing and an in-app SOS.</li>
<li><strong>Easy payments</strong> — cash, card or wallet, your choice.</li>
</ul>

<p>Thank you for riding with ReadyRide.</p>
HTML;
    }
}
