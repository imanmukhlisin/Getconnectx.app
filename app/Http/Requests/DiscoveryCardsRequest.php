<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DiscoveryCardsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'context'             => 'sometimes|array',
            'context.mode'        => 'sometimes|string|in:finding_cofounder,building_team,explore_startups,joining_startups',
            'filters'             => 'sometimes|array',
            'filters.goalId'      => 'sometimes|string',
            'filters.industryIds'         => 'sometimes|array',
            'filters.industryIds.*'       => 'string',
            'filters.skillIds'            => 'sometimes|array',
            'filters.skillIds.*'          => 'string',
            'filters.roleNeededIds'       => 'sometimes|array',
            'filters.roleNeededIds.*'     => 'string',
            'filters.skillStrengthIds'    => 'sometimes|array',
            'filters.skillStrengthIds.*'  => 'string',
            'filters.commitmentIds'       => 'sometimes|array',
            'filters.commitmentIds.*'     => 'string',
            'filters.startupStageIds'     => 'sometimes|array',
            'filters.startupStageIds.*'   => 'string',
            'filters.founderTypeIds'      => 'sometimes|array',
            'filters.founderTypeIds.*'    => 'string',
            'filters.locationAvailability'                     => 'sometimes|array',
            'filters.locationAvailability.latitude'            => 'sometimes|numeric',
            'filters.locationAvailability.longitude'           => 'sometimes|numeric',
            'filters.locationAvailability.distanceKm'          => 'sometimes|numeric|min:1|max:500',
            'filters.locationAvailability.remoteReady'         => 'sometimes|boolean',
            'filters.locationAvailability.city'                => 'sometimes|string',
            'filters.locationAvailability.workArrangementIds'  => 'sometimes|array',
            'filters.locationAvailability.workArrangementIds.*' => 'string',
            'pagination'          => 'sometimes|array',
            'pagination.limit'    => 'sometimes|integer|min:1|max:20',
            'pagination.cursor'   => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'context.mode.in'        => 'Invalid discovery mode. Allowed: finding_cofounder, building_team, explore_startups, joining_startups',
            'pagination.limit.max'   => 'Pagination limit cannot exceed 20.',
        ];
    }
}
