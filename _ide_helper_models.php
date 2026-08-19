<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $class_id
 * @property int $subject_id
 * @property string $class_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Groups> $groups
 * @property-read int|null $groups_count
 * @property-read \App\Models\Subject $subject
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClassSection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClassSection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClassSection query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClassSection whereClassId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClassSection whereClassName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClassSection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClassSection whereSubjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClassSection whereUpdatedAt($value)
 */
	class ClassSection extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $group_id
 * @property int $user_id
 * @property string $role
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Groups $group
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group_Members newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group_Members newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group_Members query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group_Members whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group_Members whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group_Members whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group_Members whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group_Members whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Group_Members whereUserId($value)
 */
	class Group_Members extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $group_id
 * @property string $group_name
 * @property int $leader_id
 * @property int|null $topic_id
 * @property int|null $class_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ClassSection|null $class
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invites> $invites
 * @property-read int|null $invites_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Join_Requests> $joinRequests
 * @property-read int|null $join_requests_count
 * @property-read \App\Models\User $leader
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $members
 * @property-read int|null $members_count
 * @property-read \App\Models\Topics|null $topic
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Topic_requests> $topicRequests
 * @property-read int|null $topic_requests_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups whereClassId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups whereGroupName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups whereLeaderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups whereTopicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Groups whereUpdatedAt($value)
 */
	class Groups extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $group_id
 * @property \App\Models\User $invitedBy
 * @property int $member_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Groups $group
 * @property-read \App\Models\User $member
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites whereInvitedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites whereMemberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Invites whereUpdatedAt($value)
 */
	class Invites extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $group_id
 * @property int $member_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Groups $group
 * @property-read \App\Models\User $member
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Join_Requests newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Join_Requests newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Join_Requests query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Join_Requests whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Join_Requests whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Join_Requests whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Join_Requests whereMemberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Join_Requests whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Join_Requests whereUpdatedAt($value)
 */
	class Join_Requests extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $subject_id
 * @property string $subject_code
 * @property string $subject_name
 * @property int|null $lecturer_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ClassSection> $classes
 * @property-read int|null $classes_count
 * @property-read \App\Models\User|null $lecturer
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Topics> $topics
 * @property-read int|null $topics_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subject newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subject newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subject query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subject whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subject whereLecturerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subject whereSubjectCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subject whereSubjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subject whereSubjectName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subject whereUpdatedAt($value)
 */
	class Subject extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $request_id
 * @property int $topic_id
 * @property int $group_id
 * @property string $status
 * @property int $created_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\Groups $group
 * @property-read \App\Models\Topics $topic
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic_requests newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic_requests newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic_requests query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic_requests whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic_requests whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic_requests whereGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic_requests whereRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic_requests whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topic_requests whereTopicId($value)
 */
	class Topic_requests extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $topic_id
 * @property string $name
 * @property string|null $description
 * @property string|null $lecturer
 * @property string|null $goal
 * @property string|null $requirements
 * @property int|null $assigned_group_id
 * @property int|null $subject_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Groups|null $assignedGroup
 * @property-read \App\Models\Subject|null $subject
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Topic_requests> $topic_requests
 * @property-read int|null $topic_requests_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereAssignedGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereLecturer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereRequirements($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereSubjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereTopicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topics whereUpdatedAt($value)
 */
	class Topics extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $user_id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $role
 * @property int $isFirstLogin
 * @property int $isHaveGroup
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ClassSection> $classes
 * @property-read int|null $classes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Groups> $groupsJoined
 * @property-read int|null $groups_joined_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Groups> $groupsLed
 * @property-read int|null $groups_led_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invites> $invites
 * @property-read int|null $invites_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Join_Requests> $joinRequests
 * @property-read int|null $join_requests_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Invites> $sentInvites
 * @property-read int|null $sent_invites_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsFirstLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsHaveGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUserId($value)
 */
	class User extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $user_id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $role
 * @property int $isFirstLogin
 * @property int $isHaveGroup
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Users newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Users newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Users query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Users whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Users whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Users whereIsFirstLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Users whereIsHaveGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Users whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Users wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Users whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Users whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Users whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Users whereUserId($value)
 */
	class Users extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $class_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|user_class newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|user_class newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|user_class query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|user_class whereClassId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|user_class whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|user_class whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|user_class whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|user_class whereUserId($value)
 */
	class user_class extends \Eloquent {}
}

