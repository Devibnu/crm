<?php

namespace App\Http\Requests\Admin;

use App\Models\CustomerSatisfaction;

class CustomerSatisfactionUpdateRequest extends CustomerSatisfactionStoreRequest
{
    protected function existingSurveyChannel(): ?string
    {
        $satisfaction = collect($this->route()?->parameters() ?? [])
            ->first(fn ($parameter) => $parameter instanceof CustomerSatisfaction);

        return $satisfaction?->survey_channel;
    }
}
