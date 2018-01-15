<?php

class SV_ThreadReplyBanner_Installer
{
    public static function install($existingAddOn, $addOnData)
    {
        $version = isset($existingAddOn['version_id']) ? $existingAddOn['version_id'] : 0;
        $db = XenForo_Application::getDb();

        $db->query(
            "
            CREATE TABLE IF NOT EXISTS xf_thread_banner (
                thread_id INT UNSIGNED NOT NULL PRIMARY KEY,
                raw_text MEDIUMTEXT,
                banner_state TINYINT(3) NOT NULL DEFAULT 1,
                banner_user_id INT NOT NULL DEFAULT 0,
                banner_edit_count INT NOT NULL DEFAULT 0,
                banner_last_edit_date INT NOT NULL DEFAULT 0,
                banner_last_edit_user_id INT NOT NULL DEFAULT 0
            ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci
        "
        );

        SV_Utils_Install::addColumn('xf_thread', 'has_banner', 'TINYINT NOT NULL DEFAULT 0');
        SV_Utils_Install::addColumn('xf_thread_banner', 'banner_state', 'TINYINT(3) NOT NULL DEFAULT 1');
        SV_Utils_Install::addColumn('xf_thread_banner', 'banner_user_id', 'INT NOT NULL DEFAULT 0');
        SV_Utils_Install::addColumn('xf_thread_banner', 'banner_edit_count', 'INT NOT NULL DEFAULT 0');
        SV_Utils_Install::addColumn('xf_thread_banner', 'banner_last_edit_date', 'INT NOT NULL DEFAULT 0');
        SV_Utils_Install::addColumn('xf_thread_banner', 'banner_last_edit_user_id', 'INT NOT NULL DEFAULT 0');

        $db->query(
            "
            INSERT IGNORE INTO xf_content_type
                (content_type, addon_id, fields)
            VALUES
                ('thread_banner', 'SV_ThreadReplyBanner', '')
        "
        );

        $db->query(
            "
            INSERT IGNORE INTO xf_content_type_field
                (content_type, field_name, field_value)
            VALUES
                ('thread_banner', 'edit_history_handler_class', 'SV_ThreadReplyBanner_EditHistoryHandler_ThreadBanner')
        "
        );

        if ($version == 0)
        {
            $db->query(
                "
                INSERT IGNORE INTO xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
                    SELECT DISTINCT user_group_id, user_id, convert(permission_group_id USING utf8), 'sv_replybanner_show', permission_value, permission_value_int
                    FROM xf_permission_entry
                    WHERE permission_group_id = 'forum' AND  permission_id IN ('postReply')
            "
            );

            $db->query(
                "
                INSERT IGNORE INTO xf_permission_entry (user_group_id, user_id, permission_group_id, permission_id, permission_value, permission_value_int)
                    SELECT DISTINCT user_group_id, user_id, convert(permission_group_id USING utf8), 'sv_replybanner_manage', permission_value, permission_value_int
                    FROM xf_permission_entry
                    WHERE permission_group_id = 'forum' AND permission_id IN ('warn','editAnyPost','deleteAnyPost')
            "
            );
        }

        if ($version < 1000402)
        {
            // clean-up orphaned thread banners.
            $db->query(
                '
                DELETE
                FROM xf_thread_banner
                WHERE NOT EXISTS (SELECT thread_id FROM xf_thread)
            '
            );
        }
    }

    public static function uninstall()
    {
        $db = XenForo_Application::get('db');

        $db->query(
            "
            DROP TABLE IF EXISTS xf_thread_banner
        "
        );

        $db->query(
            "
            DELETE FROM xf_permission_entry
            WHERE permission_group_id = 'forum' AND permission_id IN ('sv_replybanner_show', 'sv_replybanner_manage')
        "
        );

        $db->query(
            "
            DELETE 
            FROM  xf_content_type 
            WHERE content_type = 'thread_banner'
        "
        );

        $db->query(
            "
            DELETE 
            FROM  xf_content_type_field 
            WHERE content_type = 'thread_banner'
        "
        );

        $db->query(
            "
            DELETE FROM xf_edit_history
            WHERE content_type = 'thread_banner'
        "
        );

        SV_Utils_Install::dropColumn('xf_thread', 'has_banner');
    }
}
