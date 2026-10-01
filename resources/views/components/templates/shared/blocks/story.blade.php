@props(['section', 'context'])

<section class="inv-block inv-section">
    <div class="inv-container inv-narrow inv-center">
        <h2 class="inv-title">{{ $section->get('title') }}</h2>
        <p class="inv-text">{{ $section->get('subtitle') }}</p>
    </div>
</section>
