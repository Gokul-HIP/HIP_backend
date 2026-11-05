<?php

namespace App\Modules\Workflow\Services\Builder;

use App\Models\HIPUser;
use App\Models\Hospital;
use App\Modules\Workflow\Models\Workflow;
use InvalidArgumentException;

class WorkflowHospitalScopeService
{
    /**
     * Determine whether the given user is a super admin with unrestricted access.
     */
    public function isSuperAdmin(?HIPUser $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->role === 'super-admin'
            || $user->role === 'super-admin-hip'
            || $user->isSuperAdmin()
            || $user->isSuperAdminHip()
            || (method_exists($user, 'hasRole') && ($user->hasRole('super-admin') || $user->hasRole('super-admin-hip')));
    }

    /**
     * Resolve and validate hospital & organization scope for creating a workflow.
     *
     * Rules:
     * 1. Super admin: full access, can target any valid hospital (or null).
     * 2. Hospital admin ($user->hospital_id):
     *    - If a requested hospital_id is passed and differs from their own hospital_id: denied!
     *    - Hospital ID is forced to their own $user->hospital_id.
     * 3. Organization admin ($user->organization_id):
     *    - If hospital_id is passed, it must belong to their organization.
     * 4. User with neither hospital_id nor organization_id (and not super-admin):
     *    - Denied! They have no hospital/org scope to manage workflows.
     *
     * @param  array<string, mixed>  $input
     * @return array{hospital_id: ?int, organization_id: ?int}
     *
     * @throws InvalidArgumentException
     */
    public function resolveForCreate(?HIPUser $user, array $input = []): array
    {
        $requestedHospitalId = isset($input['hospital_id']) && is_numeric($input['hospital_id'])
            ? (int) $input['hospital_id']
            : null;

        $requestedOrgId = isset($input['organization_id']) && is_numeric($input['organization_id'])
            ? (int) $input['organization_id']
            : null;

        if ($user && ! $this->isSuperAdmin($user)) {
            // Unscoped user (neither hospital_id nor organization_id)
            if (($user->hospital_id === null || (int) $user->hospital_id <= 0)
                && ($user->organization_id === null || (int) $user->organization_id <= 0)) {
                throw new InvalidArgumentException('You are not authorized to manage workflows for this hospital.');
            }

            // Hospital admin: must be forced from their own hospital_id
            if ($user->hospital_id !== null && (int) $user->hospital_id > 0) {
                if ($requestedHospitalId !== null && $requestedHospitalId !== (int) $user->hospital_id) {
                    throw new InvalidArgumentException('You are not authorized to manage workflows for this hospital.');
                }
                $requestedHospitalId = (int) $user->hospital_id;
            }
        }

        // Validate hospital existence and user access
        $this->assertHospitalAccess($user, $requestedHospitalId, $requestedOrgId);

        $orgId = $requestedOrgId;
        if ($requestedHospitalId !== null && $orgId === null) {
            $hospital = Hospital::query()->find($requestedHospitalId);
            $orgId = $hospital?->organization_id;
        }

        return [
            'hospital_id' => $requestedHospitalId,
            'organization_id' => $orgId,
        ];
    }

    /**
     * Resolve and validate hospital & organization scope for updating an existing workflow.
     *
     * Rules:
     * - The hospital must be taken from the stored workflow when editing (not from the browser).
     * - If the client tampers with hospital_id (passes a different one):
     *   - If not super admin, denied!
     * - Access to the stored workflow's hospital is asserted against the user.
     *
     * @param  array<string, mixed>  $input
     * @return array{hospital_id: ?int, organization_id: ?int}
     *
     * @throws InvalidArgumentException
     */
    public function resolveForUpdate(?HIPUser $user, Workflow $workflow, array $input = []): array
    {
        $requestedHospitalId = array_key_exists('hospital_id', $input) && $input['hospital_id'] !== null
            ? (int) $input['hospital_id']
            : null;

        // Tamper check: non-super-admin cannot tamper hospital_id to another hospital
        if ($requestedHospitalId !== null
            && $workflow->hospital_id !== null
            && $requestedHospitalId !== (int) $workflow->hospital_id) {
            if ($user && ! $this->isSuperAdmin($user)) {
                throw new InvalidArgumentException('You are not authorized to manage workflows for this hospital.');
            }
        }

        // The hospital must be taken from the stored workflow when editing
        $effectiveHospitalId = $workflow->hospital_id;
        $effectiveOrgId = $workflow->organization_id;

        // Check access to the stored workflow's hospital
        $this->assertHospitalAccess($user, $effectiveHospitalId, $effectiveOrgId);

        return [
            'hospital_id' => $effectiveHospitalId,
            'organization_id' => $effectiveOrgId,
        ];
    }

    /**
     * Assert that the user is authorized to manage the given hospital and organization.
     *
     * @throws InvalidArgumentException
     */
    public function assertHospitalAccess(?HIPUser $user, ?int $hospitalId, ?int $workflowOrgId = null): void
    {
        if ($user && ! $this->isSuperAdmin($user)) {
            // Unscoped user (neither hospital_id nor organization_id)
            if (($user->hospital_id === null || (int) $user->hospital_id <= 0)
                && ($user->organization_id === null || (int) $user->organization_id <= 0)) {
                throw new InvalidArgumentException('You are not authorized to manage workflows for this hospital.');
            }
        }

        if ($hospitalId === null) {
            return;
        }

        $hospital = Hospital::query()->find($hospitalId);
        if (! $hospital) {
            throw new InvalidArgumentException('Hospital is invalid.');
        }

        if ($workflowOrgId !== null && (int) $hospital->organization_id !== (int) $workflowOrgId) {
            throw new InvalidArgumentException('Hospital does not belong to the workflow organization.');
        }

        if ($user && ! $user->canAccessHospital($hospitalId)) {
            throw new InvalidArgumentException('You are not authorized to manage workflows for this hospital.');
        }
    }
}
