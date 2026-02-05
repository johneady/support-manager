<?php

namespace App\Enums;

enum TicketCategory: string
{
    case TechnicalIssue = 'technical_issue';
    case FeatureRequest = 'feature_request';
    case GeneralInquiry = 'general_inquiry';

    public function label(): string
    {
        return match ($this) {
            self::TechnicalIssue => 'Technical Issue',
            self::FeatureRequest => 'Feature Request',
            self::GeneralInquiry => 'General Inquiry',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TechnicalIssue => 'red',
            self::FeatureRequest => 'blue',
            self::GeneralInquiry => 'zinc',
        };
    }
}
