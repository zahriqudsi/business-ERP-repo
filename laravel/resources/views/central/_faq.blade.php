@php
    $product = config('sjpos.product_name');
    $faqs = [
        ['Do I need to install anything?', "No. {$product} works in your web browser on a computer, tablet or phone. Your business gets its own address, such as yourshop.".config('tenancy.base_domain').'.'],
        ['What happens after the 14-day trial?', 'Choose a plan and pay by bank transfer to keep using '.$product.'. If the trial ends without payment, your account is paused after a grace period, and nothing is deleted.'],
        ['Is my data safe and private?', 'Yes. Each business has its own private database that no other business can reach, it is backed up every night, and owners can download a copy at any time.'],
        ['Can my staff use it too?', 'Yes. Add a login for each person with the role owner, manager or staff. Staff can do day-to-day work but cannot delete records or see reports.'],
        ['Can I add tax such as VAT?', 'Yes. Switch on tax in Settings with your rate and registration number, and choose per invoice or quotation whether to add it.'],
        ['Can I move my existing customers and products in?', 'Yes. Add them as you go, or contact us and we will help you get started.'],
    ];
    $faqId = 'faq'.uniqid();
@endphp
<div class="accordion bl-faq" id="{{ $faqId }}">
    @foreach ($faqs as $i => [$question, $answer])
        <div class="accordion-item">
            <h3 class="accordion-header">
                <button class="accordion-button {{ $i ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $faqId }}-{{ $i }}" aria-expanded="{{ $i ? 'false' : 'true' }}">{{ $question }}</button>
            </h3>
            <div id="{{ $faqId }}-{{ $i }}" class="accordion-collapse collapse {{ $i ? '' : 'show' }}" data-bs-parent="#{{ $faqId }}">
                <div class="accordion-body text-muted-2">{{ $answer }}</div>
            </div>
        </div>
    @endforeach
</div>
