<?php

// One-off cleanup: remove orphaned comments left in GroupService.php (regex, ASCII-only patterns)
// Run: php _patch_group_service3.php  (from project root)

$path = 'app/Services/GroupService.php';
$s = file_get_contents($path);
$orig = $s;

// 1) orphaned promoteToLeader docblock directly above the new hasGroupInClass docblock
$pattern = '#/\*\n     \* Chuy.*?\*/\n    /\*\n     \* Bugfix B2 \[R71\]: does this#s';
if (preg_match($pattern, $s)) {
    $s = preg_replace(
        $pattern,
        '    /**\n     * Bugfix B2 [R71]: does this',
        $s
    );
}

// 2) orphaned promoteToLeader call comment inside createGroupByStudent
$pattern2 = '#// Chuy[^\n]*\n\n(\s*return \$group;)#';
if (preg_match($pattern2, $s)) {
    $s = preg_replace($pattern2, '$1', $s);
}

if ($s !== $orig) {
    file_put_contents($path, $s);
    echo "GroupService.php cleaned OK\n";
} else {
    echo "Nothing to clean\n";
}