<?php

// One-off patch script: Bugfix B1 [R71] / B2 [R71] / B3 [R13] in GroupService.php
// Run: php _patch_group_service.php  (from project root)
// Afterwards: php -l app/Services/GroupService.php to verify, then delete this file.

$path = 'app/Services/GroupService.php';
$s = file_get_contents($path);

function apply_patch(string $old, string $new): void
{
    global $s;
    if (!str_contains($s, $old)) {
        throw new RuntimeException('ANCHOR NOT FOUND: ' . substr($old, 0, 90));
    }
    if (substr_count($s, $old) > 1) {
        throw new RuntimeException('ANCHOR NOT UNIQUE: ' . substr($old, 0, 90));
    }
    $s = str_replace($old, $new, $s);
}

// 1) B2: addMember no longer touches the removed is_have_group column
apply_patch(
    "        \$group->members()->attach(\$member->user_id, ['role' => 'member']);\n        \$member->update(['is_have_group' => true]);\n",
    "        \$group->members()->attach(\$member->user_id, ['role' => 'member']);\n"
);

// 2) B1: promoteToLeader removed (role stays 'student'); replaced with new helpers
apply_patch(
    "    public function promoteToLeader(User \$user): void\n    {\n        \$user->update([\n            'role' => 'leader',\n            'is_have_group' => true,\n        ]);\n    }\n",
    "    /**\n     * Bugfix B2 [R71]: does this student already belong to a group in this class\n     * (as leader or as pivot member)? Business rule: one group per class/subject.\n     */\n    public function hasGroupInClass(User \$user, int \$classId): bool\n    {\n        return Groups::where('class_id', \$classId)\n                ->where('leader_id', \$user->user_id)\n                ->exists()\n            || Group_Members::where('user_id', \$user->user_id)\n                ->whereHas('group', function (\$q) use (\$classId) {\n                    \$q->where('class_id', \$classId);\n                })\n                ->exists();\n    }\n\n    /**\n     * Bugfix B2 [R71]: does this student belong to any group in any class?\n     */\n    public function hasGroupInAnyClass(User \$user): bool\n    {\n        return \$this->isLeaderOfAnyGroup(\$user) || \$this->isMemberOfAnyGroup(\$user);\n    }\n\n    /**\n     * Bugfix B3 [R13]: a student removed from a class must also be removed from\n     * that class's groups:\n     * - detach from group_members of groups in the class;\n     * - if student is a group leader: transfer leadership to the first member,\n     *   or delete the group when it has no members left;\n     * - cancel pending invites / join requests of the student in that class.\n     */\n    public function removeUserFromClassGroups(User \$student, int \$classId): void\n    {\n        \$groupIds = Groups::where('class_id', \$classId)->pluck('group_id')->toArray();\n\n        if (empty(\$groupIds)) {\n            return;\n        }\n\n        DB::transaction(function () use (\$student, \$classId, \$groupIds) {\n            // 1. detach member from groups of this class\n            Group_Members::where('user_id', \$student->user_id)\n                ->whereIn('group_id', \$groupIds)\n                ->delete();\n\n            // 2. groups this student leads in this class\n            \$ledGroups = Groups::where('class_id', \$classId)\n                ->where('leader_id', \$student->user_id)\n                ->get();\n\n            foreach (\$ledGroups as \$group) {\n                \$this->disbandOrTransferLeadership(\$group);\n            }\n\n            // 3. cancel pending invites / join requests of the student in this class\n            Invites::where('member_id', \$student->user_id)\n                ->whereIn('group_id', \$groupIds)\n                ->where('status', 'Pending')\n                ->delete();\n\n            Join_Requests::where('member_id', \$student->user_id)\n                ->whereIn('group_id', \$groupIds)\n                ->where('status', 'Pending')\n                ->delete();\n        });\n    }\n\n    /**\n     * Group whose leader left: transfer leadership to the first member, or delete\n     * the group (with cleanup) when there are no members left.\n     */\n    private function disbandOrTransferLeadership(Groups \$group): void\n    {\n        \$members = \$group->members()->orderBy('group_members.id')->get();\n\n        if (\$members->isNotEmpty()) {\n            \$newLeader = \$members->first();\n            \$group->update(['leader_id' => \$newLeader->user_id]);\n            \$this->updateStatus(\$group);\n            return;\n        }\n\n        \$group->invites()->delete();\n        \$group->joinRequests()->delete();\n        \$group->topicRequests()->delete();\n        \$group->members()->detach();\n        \$group->delete();\n    }\n"
);

// 3) B2: createGroupByStudent now checks per class
apply_patch(
    "        if (\$this->isLeaderOfAnyGroup(\$user) || \$this->isMemberOfAnyGroup(\$user)) {\n",
    "        if (\$this->hasGroupInClass(\$user, \$classId)) {\n"
);

// 4) B1: no longer promote role to 'leader' after group creation
apply_patch(
    "            \$this->promoteToLeader(\$user);\n",
    ""
);
apply_patch(
    "            \$this->promoteToLeader(\$leader);\n",
    ""
);

// 5) B1: lecturer-created group leader must be a 'student' (role 'leader' no longer exists)
apply_patch(
    "        if (!\$leader || !in_array(\$leader->role, ['student', 'leader'])) {\n",
    "        if (!\$leader || \$leader->role !== 'student') {\n"
);

// 6) B2: createGroupByLecturer checks per class
apply_patch(
    "        if (\$this->isLeaderOfAnyGroup(\$leader) || \$this->isMemberOfAnyGroup(\$leader)) {\n",
    "        if (\$this->hasGroupInClass(\$leader, \$classId)) {\n"
);

file_put_contents($path, $s);
echo "GroupService.php patched OK\n";