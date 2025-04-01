<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class testarjobzera extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:testarjobzera';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userData = [
            "app_role_group" => [
                "balerion:store-owner:beer-store",
                "balerion:god:maringa"
            ],
            "permissions_by_role" => [
                "god" => [
                    "order:report",
                    "order:read",
                    "order:list"
                ],
                "store-owner" => [
                    "order:list"
                ]
            ],
            "groups" => [
                [
                    "uuid" => "49576ebe-c50c-4907-af5d-c6afc04d0ff1",
                    "name" => "beer-store",
                    "full_path" => "/brasil/sorocaba/beer-store",
                    "path" => "beer-store",
                    "parent" => [
                        "uuid" => "a83adfa4-15fb-49e8-9651-f4119e340bdc",
                        "name" => "sorocaba",
                        "full_path" => "/brasil/sorocaba",
                        "path" => "sorocaba",
                        "parent" => [
                            "uuid" => "79d1cdba-f8fc-4b30-af59-ad4e6c5cfd90",
                            "name" => "brasil",
                            "full_path" => "/brasil",
                            "path" => "brasil",
                            "parent" => null
                        ]
                    ]
                ],
                [
                    "uuid" => "cf53edb7-f96f-478e-8be1-35546c7f2514",
                    "name" => "maringa",
                    "full_path" => "/brasil/maringa",
                    "path" => "maringa",
                    "parent" => [
                        "uuid" => "79d1cdba-f8fc-4b30-af59-ad4e6c5cfd90",
                        "name" => "brasil",
                        "full_path" => "/brasil",
                        "path" => "brasil",
                        "parent" => null
                    ]
                ]
            ]
        ];

// Exemplo de permissões a serem verificadas
        $requestedPermissions = [
            "49576ebe-c50c-4907-af5d-c6afc04d0ff1" => "order:list",  // beer-store - order:list (deve autorizar)
            // "49576ebe-c50c-4907-af5d-c6afc04d0ff1" => "order:report",  // beer-store - order:report (não deve autorizar)
            // "cf53edb7-f96f-478e-8be1-35546c7f2514" => "order:report",  // maringa - order:report (deve autorizar)
        ];

        dd($this->authorize($userData, $requestedPermissions));
    }










    /**
     * Check if user has the requested permissions
     *
     * @param array $userData User data from Keycloak
     * @param array $requestedPermissions Array of requested permissions in format ["group_uuid" => "realm_role_name"]
     * @return bool
     */
    public function authorize(array $userData, array $requestedPermissions): bool
    {
        // Convert user data to more usable structure
        $userGroups = $this->getUserGroups($userData);
        $userPermissions = $this->getUserPermissions($userData);

        // Check each requested permission
        foreach ($requestedPermissions as $groupUuid => $permissionName) {
            if (!$this->hasPermission($userGroups, $userPermissions, $groupUuid, $permissionName)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user has specific permission for a group
     */
    private function hasPermission(
        Collection $userGroups,
        Collection $userPermissions,
        string $requestedGroupUuid,
        string $requestedPermission
    ): bool {
        // Get all relevant groups (requested group and all parent groups)
        $relevantGroups = $this->getRelevantGroups($userGroups, $requestedGroupUuid);

        if ($relevantGroups->isEmpty()) {
            return false;
        }

        // Check if user has permission for any of these groups
        foreach ($relevantGroups as $group) {
            // Get all role-client combinations for this group from app_role_resource
            $groupPermissions = $userPermissions->filter(function ($item) use ($group) {
                return $item['group'] === $group['name'];
            });

            foreach ($groupPermissions as $permission) {
                // Check if the role has the requested permission
                if (isset($permission['permissions']) && in_array($requestedPermission, $permission['permissions'])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get all relevant groups for authorization (requested group and its parent groups)
     * Uses the parent structure that's already in the userData
     */
    private function getRelevantGroups(Collection $userGroups, string $requestedGroupUuid): Collection
    {
        $result = collect();

        // Find the requested group in user's groups or its parent groups
        foreach ($userGroups as $userGroup) {
            // Check if this is the requested group
            if ($userGroup['uuid'] === $requestedGroupUuid) {
                $result->push($userGroup);
                return $result;
            }

            // Check if the requested group is a child of this group by traversing the hierarchy
            $childGroup = $this->findGroupInHierarchy($userGroup, $requestedGroupUuid);
            if ($childGroup) {
                // We found that the requested group is a child of this user group,
                // so the user has access through parent inheritance
                $result->push($userGroup);
                return $result;
            }
        }

        return $result;
    }

    /**
     * Recursively search for a group UUID in the group hierarchy
     */
    private function findGroupInHierarchy(array $group, string $searchUuid): ?array
    {
        // Check if this is the group we're looking for
        if ($group['uuid'] === $searchUuid) {
            return $group;
        }

        // If this group has a parent, check if parent structure exists
        // Note: In your structure, it appears "parent" might be nested differently than expected,
        // so adjust this logic based on your actual structure
        if (isset($group['parent']) && is_array($group['parent'])) {
            return $this->findGroupInHierarchy($group['parent'], $searchUuid);
        }

        return null;
    }

    /**
     * Build a structured collection of user groups
     */
    private function getUserGroups(array $userData): Collection
    {
        return collect($userData['groups'] ?? []);
    }

    /**
     * Build a structured collection of user permissions
     */
    private function getUserPermissions(array $userData): Collection
    {
        $permissions = collect();
        $appRoleGroup = $userData['app_role_group'] ?? [];
        $permissionsByRole = $userData['permissions_by_role'] ?? [];

        foreach ($appRoleGroup as $entry) {
            [$client, $role, $group] = explode(':', $entry);

            $permissions->push([
                'client' => $client,
                'role' => $role,
                'group' => $group,
                'permissions' => $permissionsByRole[$role] ?? []
            ]);
        }

        return $permissions;
    }
}
