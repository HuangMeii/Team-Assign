<?php

// One-off cleanup: remove orphaned comments left in GroupService.php
// Run: php _patch_group_service2.php  (from project root)
// If any anchor is not found the script aborts WITHOUT writing.

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

// Remove orphaned promoteToLeader docblock that now sits above hasGroupInClass
apply_patch(
    "    /**\n     * Chuyển vai trò Sinh vién -> Nhóm trưụng sau khi tạo nhóm.\n     */\n    /**\n     * Bugfix B2 [R71]: does this student already belong to a group in this class",
    "    /**\n     * Bugfix B2 [R71]: does this student already belong to a group in this class"
);

// Remove orphaned promoteToLeader call comment inside createGroupByStudent
apply_patch(
    "            // Chuyển vai trò Sinh vién -> Nhóm trưụng\n\n            return \$group;",
    "            return \$group;"
);

file_put_contents($path, $s);
echo "GroupService.php cleaned OK\n";