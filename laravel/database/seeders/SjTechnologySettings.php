<?php

namespace Database\Seeders;

/**
 * The original SJ Technology business details that used to be hard-coded in config/sjpos.php.
 * Carried into the settings table for that existing install, and used by the tests as realistic values.
 */
final class SjTechnologySettings
{
    /**
     * Setting key (path under config "sjpos") => value.
     *
     * @var array<string, mixed>
     */
    public const VALUES = [
        'company.name' => 'SJ Technology',
        'company.tagline' => 'Laptop Repairs, Accessories & Electronics',
        'company.address' => "No 07,\nAlvis Place, Attidiya,\nDehiwala.",
        'company.phone' => '+94 779 194 928',
        'company.email' => 'sjtechnology365@gmail.com',
        'company.website' => 'sjtechnology.lk',
        'company.job_phone' => '+94 77 919 4928',
        'company.job_email' => 'shane@sjtechnology.lk',
        'company.logo' => 'assets/img/SJ-TECHNOLOGY-final.png',
        'bank.account_name' => 'Sj Marian',
        'bank.bank_name' => 'Commercial Bank',
        'bank.account_number' => '8016893890',
        'bank.branch' => 'Attidiya Branch',
        'invoice.first_number' => 1281,
        'invoice.default_terms' => "1. Warranty does not cover damages for items such as any physical damages.\n2. Invoices must be produced for any warranty claims.\n3. Warranty is valid based on the subscription period.",
        'invoice.default_note' => 'Cheques should be drawn in favour of “SJ Technology”.',
        'quotation.first_number' => 1180,
        'quotation.default_terms' => "1. Delivery: Ex-stock else 4-6 weeks, from the date of confirmation.\n2. Validity: Offer is valid for 7 days.\n3. Acceptance: On a written order confirmation.\n4. Payments: 100% Advance Upon confirmation.",
        'job.first_number' => 10,
        'job.default_terms' => "The device is accepted for repair on the understanding that the customer has backed up their data. SJ TECHNOLOGY is not liable for any data loss or damage during diagnosis or repair.\n"
            ."A detailed diagnosis will be carried out and the customer will be informed of the issue and the estimated cost (if applicable) before repair is started.\n"
            ."Repair work will be started only after customer approval.\n"
            ."The accessories received with the device are listed above. SJ TECHNOLOGY is not responsible for any missing items not listed in this job note.\n"
            .'Once the repair is completed, the customer will be notified. We will safely deliver the device to the customer at the given address.',
    ];
}
