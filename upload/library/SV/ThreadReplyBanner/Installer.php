<?php

class SV_ThreadReplyBanner_Installer
{
    public static function install($existingAddOn, $addOnData)
    {
        $version = isset($existingAddOn['version_id']) ? $existingAddOn['version_id'] : 0;
        $db = XenForo_Application::getDb();

        $db->query("
            CREATE TABLE IF NOT EXISTS xf_thread_banner (
                thread_id INT UNSIGNED NOT NULL PRIMARY KEY,
                raw_text mediumtext
            ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci
        ");

        SV_Utils_Install::addColumn('xf_thread', 'has_banner', 'TINYINT NOT NULL DEFAULT 0');

        if ($version == 0)
        {
            $db->query("
                insert ignore into xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
                    select distinct user_group_id, user_id, convert(permission_group_id using utf8), 'sv_replybanner_show', permission_value, permission_value_int
                    from xf_permission_entry
                    where permission_group_id = 'forum' and  permission_id in ('postReply')
            ");

            $db->query("
                insert ignore into xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
                    select distinct user_group_id, user_id, convert(permission_group_id using utf8), 'sv_replybanner_manage', permission_value, permission_value_int
                    from xf_permission_entry
                    where permission_group_id = 'forum' and permission_id in ('warn','editAnyPost','deleteAnyPost')
            ");
            XenForo_Model::create('XenForo_Model_Permission')->rebuildPermissionCache();
        }

        if ($version < 1000402)
        {
            // clean-up orphaned thread banners.
            $db->query('
                DELETE
                FROM xf_thread_banner
                WHERE NOT EXISTS (SELECT thread_id FROM xf_thread)
            ');
        }

        return true;
    }

    public static function uninstall()
    {
        $db = XenForo_Application::get('db');

        $db->query("
            DROP TABLE IF EXISTS xf_thread_banner
        ");

        $db->query("
            DELETE FROM xf_permission_entry
            WHERE permission_group_id = 'forum' and permission_id in ('sv_replybanner_show', 'sv_replybanner_manage')
        ");

        SV_Utils_Install::dropColumn('xf_thread', 'has_banner');
        XenForo_Model::create('XenForo_Model_Permission')->rebuildPermissionCache();

        return true;
    }
}