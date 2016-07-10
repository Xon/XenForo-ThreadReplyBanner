<?php

class SV_ThreadReplyBanner_XenForo_Model_Thread extends XFCP_SV_ThreadReplyBanner_XenForo_Model_Thread
{
    public function getRawThreadReplyBanner($threadId)
    {
        return $this->_getDb()->fetchOne("select raw_text from xf_thread_banner where thread_id = ?", $threadId);
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
            $cacheId = 'thread_banner_'.$thread['thread_id'];
            if ($banner = $cacheObject->load($cacheId, true))
            {
                return $banner;
            }
        }
        $banner = $this->getRawThreadReplyBanner($thread['thread_id']);

        if (!empty($banner))
        {
            $bbCodeParser = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Base'));
            $banner = new XenForo_BbCode_TextWrapper($banner, $bbCodeParser);

            if ($cacheObject)
            {
                // convert to a string context to save
                $banner = '' . $banner;
                $cacheObject->save($banner, $cacheId, array(), 86400);
            }
        }

        return $banner;
    }

    public function updateThreadReplyBanner($threadId, $text)
    {
        $cacheId = 'thread_banner_'.$threadId;
        $cacheObject = $this->_getCache(true);
        $db = $this->_getDb();
        if(empty($text))
        {
            $db->query("delete from xf_thread_banner where thread_id = ? ", $threadId);
            if ($cacheObject)
            {
                $cacheObject->remove($cacheId);
            }
        }
        else
        {
            $db->query("insert xf_thread_banner (thread_id, raw_text) values (?,?) ON DUPLICATE KEY UPDATE raw_text = VALUES(raw_text) ", array($threadId,$text));
            if ($cacheObject)
            {
                $bbCodeParser = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Base'));
                $banner = new XenForo_BbCode_TextWrapper($text, $bbCodeParser);
                $cacheObject->save('' . $banner, $cacheId, array(), 86400);
            }
        }
        $db->query("update xf_thread
            set has_banner = exists(select thread_id
                                    from xf_thread_banner
                                    where xf_thread_banner.thread_id = xf_thread.thread_id)
            where thread_id = ?", $threadId);
    }

    public function canManageThreadReplyBanner(array $thread, array $forum, &$errorPhraseKey = '', array $nodePermissions = null, array $viewingUser = null)
    {
        $this->standardizeViewingUserReferenceForNode($thread['node_id'], $viewingUser, $nodePermissions);

        return XenForo_Permission::hasContentPermission($nodePermissions, 'sv_replybanner_manage');
    }
}