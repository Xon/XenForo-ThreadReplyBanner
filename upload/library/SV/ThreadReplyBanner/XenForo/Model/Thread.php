<?php

class SV_ThreadReplyBanner_XenForo_Model_Thread extends XFCP_SV_ThreadReplyBanner_XenForo_Model_Thread
{
    public function getRawThreadReplyBanner($threadId)
    {
        return $this->_getDb()->fetchRow("SELECT * FROM xf_thread_banner WHERE thread_id = ?", $threadId);
    }

    public function getThreadReplyBannerParser()
    {
        return XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Base'));
    }

    public function renderThreadReplyBanner(XenForo_BbCode_Parser $bbCodeParser, array $banner)
    {
        return $bbCodeParser->render($banner['raw_text']);
    }

    public function getThreadReplyBannerCacheId($threadId)
    {
        return 'thread_banner_' . $threadId;
    }


    public function getThreadReplyBanner($thread, array $nodePermissions = null, array $viewingUser = null)
    {
        if (empty($thread['has_banner']))
        {
            return null;
        }

        $this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);

        if (!XenForo_Permission::hasContentPermission($nodePermissions, 'sv_replybanner_show') &&
            !XenForo_Permission::hasContentPermission($nodePermissions, 'sv_replybanner_manage'))
        {
            return null;
        }

        if ($cacheObject = $this->_getCache(true))
        {
            $cacheId = $this->getThreadReplyBannerCacheId($thread['thread_id']);
            if ($bannerText = $cacheObject->load($cacheId, true))
            {
                return $bannerText;
            }
        }
        $bannerText = '';
        $banner = $this->getRawThreadReplyBanner($thread['thread_id']);

        if (!empty($banner))
        {
            $bbCodeParser = $this->getThreadReplyBannerParser();
            $bannerText = $this->renderThreadReplyBanner($bbCodeParser, $banner);

            if ($cacheObject)
            {
                $cacheObject->save($bannerText, $cacheId, [], 86400);
            }
        }

        return $bannerText;
    }

    public function updateThreadReplyBanner($threadId, $text)
    {
        $cacheId = $this->getThreadReplyBannerCacheId($threadId);
        $cacheObject = $this->_getCache(true);
        $db = $this->_getDb();
        if (empty($text))
        {
            $db->query("DELETE FROM xf_thread_banner WHERE thread_id = ? ", $threadId);
            if ($cacheObject)
            {
                $cacheObject->remove($cacheId);
            }
        }
        else
        {
            $db->query("insert xf_thread_banner (thread_id, raw_text) values (?,?) ON DUPLICATE KEY UPDATE raw_text = VALUES(raw_text) ", [$threadId, $text]);
            if ($cacheObject)
            {
                $banner = $this->getRawThreadReplyBanner($threadId);
                $bbCodeParser = $this->getThreadReplyBannerParser();
                $bannerText = $this->renderThreadReplyBanner($bbCodeParser, $banner);
                $cacheObject->save($bannerText, $cacheId, [], 86400);
            }
        }
        $db->query(
            "UPDATE xf_thread
            SET has_banner = exists(SELECT thread_id
                                    FROM xf_thread_banner
                                    WHERE xf_thread_banner.thread_id = xf_thread.thread_id)
            WHERE thread_id = ?", $threadId
        );
    }

    public function canManageThreadReplyBanner(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);

        return XenForo_Permission::hasContentPermission($nodePermissions, 'sv_replybanner_manage');
    }
}
