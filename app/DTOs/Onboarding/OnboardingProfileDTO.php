<?php

namespace App\DTOs\Onboarding;

use App\Http\Requests\Onboarding\OnboardingStepTwoRequest;


class OnboardingProfileDTO
{
    public function __construct(
        public readonly string $userName,
        public readonly string $partner1Name,
        public readonly string $partner2Name,
    ) {}

    public static function fromRequest(OnboardingStepTwoRequest $request): self
    {
        return new self(
            userName: trim($request->validated('name')),
            partner1Name: trim($request->validated('partner_1_name')),
            partner2Name: trim($request->validated('partner_2_name')),
        );
    }
}
